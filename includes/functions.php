<?php
/**
 * Common Functions Library
 * Smart Instructor Coordination and Workload Management System
 * 
 * All reusable functions for workload, suggestions, conflict checking, etc.
 * Written in beginner-friendly style with clear comments.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Sanitize user input (prevent XSS)
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Log user activity (for audit trail)
 */
function logActivity($userId, $action, $description = '') {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $action, $description, $ip]);
    } catch (Exception $e) {
        // Fail silently in production (or log to file)
        error_log("Activity log failed: " . $e->getMessage());
    }
}

/**
 * Create in-app notification
 */
function createNotification($userId, $title, $message, $type = 'info', $relatedId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $title, $message, $type, $relatedId]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * =====================================================
 * WORKLOAD CALCULATION
 * =====================================================
 * Calculates total workload hours for an instructor
 * EXCLUDES presentation panel assignments (is_presentation_panel = 1)
 */
function calculateWorkload($instructorId, $fromDate = null, $toDate = null) {
    global $pdo;
    
    if ($fromDate === null) $fromDate = date('Y-m-01'); // First day of current month
    if ($toDate === null) $toDate = date('Y-m-t');     // Last day of current month
    
    $sql = "
        SELECT COALESCE(SUM(duration_hours), 0) as total_hours
        FROM task_assignments
        WHERE instructor_id = :instructor_id
          AND is_presentation_panel = 0
          AND status IN ('Assigned', 'Accepted', 'Completed')
          AND scheduled_date BETWEEN :from_date AND :to_date
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':instructor_id' => $instructorId,
        ':from_date' => $fromDate,
        ':to_date' => $toDate
    ]);
    
    $result = $stmt->fetch();
    return (float)($result['total_hours'] ?? 0);
}

/**
 * Get current workload percentage (compared to max)
 */
function getWorkloadPercentage($instructorId) {
    $current = calculateWorkload($instructorId);
    $max = DEFAULT_MAX_WEEKLY_HOURS; // from config.php
    
    if ($max <= 0) return 0;
    
    $percentage = ($current / $max) * 100;
    return min(round($percentage, 1), 100); // Cap at 100%
}

/**
 * =====================================================
 * SMART INSTRUCTOR SUGGESTION ALGORITHM
 * =====================================================
 * Simple, explainable algorithm for second-year students:
 * 1. Must be active instructor
 * 2. Not on approved leave during the period
 * 3. No timetable conflict
 * 4. No existing task assignment conflict
 * 5. Preferably matching academic stream (if provided)
 * 6. Sort by lowest current workload
 */
function getSmartSuggestions($taskTypeId, $date, $startTime, $endTime, $streamId = null, $limit = 5) {
    global $pdo;
    
    $suggestions = [];
    
    // Step 1 & 2: Get all active instructors not on leave that day
    $sql = "
        SELECT i.*, u.full_name, ast.name as stream_name, d.name as dept_name,
               (SELECT COALESCE(SUM(ta.duration_hours), 0) 
                FROM task_assignments ta 
                WHERE ta.instructor_id = i.id 
                  AND ta.is_presentation_panel = 0
                  AND ta.scheduled_date BETWEEN DATE_SUB(:workload_date_from, INTERVAL 30 DAY) AND :workload_date_to
               ) as recent_workload
        FROM instructors i
        JOIN users u ON i.user_id = u.id
        JOIN academic_streams ast ON i.academic_stream_id = ast.id
        JOIN departments d ON i.department_id = d.id
        WHERE i.status = 'active'
          AND NOT EXISTS (
              SELECT 1 FROM leave_records lr 
              WHERE lr.instructor_id = i.id 
                AND lr.status = 'Approved'
                AND :leave_check_date BETWEEN lr.start_date AND lr.end_date
          )
    ";
    
    $params = [
        ':workload_date_from' => $date,
        ':workload_date_to' => $date,
        ':leave_check_date' => $date
    ];
    
    if ($streamId) {
        $sql .= " AND i.academic_stream_id = :stream_id";
        $params[':stream_id'] = $streamId;
    }
    
    $sql .= " ORDER BY recent_workload ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $instructors = $stmt->fetchAll();
    
    foreach ($instructors as $instructor) {
        // Step 3: Check timetable conflict
        if (hasTimetableConflict($instructor['id'], $date, $startTime, $endTime)) {
            continue;
        }
        
        // Step 4: Check existing task assignment conflict
        if (hasTaskConflict($instructor['id'], $date, $startTime, $endTime)) {
            continue;
        }
        
        // Step 5 & 6: Add to suggestions (already sorted by workload)
        $suggestions[] = [
            'instructor_id' => $instructor['id'],
            'name' => $instructor['full_name'],
            'employee_id' => $instructor['employee_id'],
            'stream' => $instructor['stream_name'],
            'department' => $instructor['dept_name'],
            'current_workload' => round($instructor['recent_workload'], 1),
            'designation' => $instructor['designation']
        ];
        
        if (count($suggestions) >= $limit) {
            break;
        }
    }
    
    return $suggestions;
}

/**
 * Check if instructor has timetable conflict on given date/time
 */
function hasTimetableConflict($instructorId, $date, $startTime, $endTime) {
    global $pdo;
    
    // Get day of week from date
    $dayOfWeek = date('l', strtotime($date)); // Monday, Tuesday, etc.
    
    $sql = "
        SELECT COUNT(*) as conflict_count
        FROM timetable_slots
        WHERE instructor_id = :instructor_id
          AND day_of_week = :day_of_week
          AND (
              (start_time < :end_time AND end_time > :start_time)  -- Overlap condition
          )
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':instructor_id' => $instructorId,
        ':day_of_week' => $dayOfWeek,
        ':start_time' => $startTime,
        ':end_time' => $endTime
    ]);
    
    $result = $stmt->fetch();
    return ($result['conflict_count'] ?? 0) > 0;
}

/**
 * Check if instructor has task assignment conflict
 */
function hasTaskConflict($instructorId, $date, $startTime, $endTime) {
    global $pdo;
    
    $sql = "
        SELECT COUNT(*) as conflict_count
        FROM task_assignments
        WHERE instructor_id = :instructor_id
          AND scheduled_date = :date
          AND status IN ('Assigned', 'Accepted')
          AND (
              (start_time < :end_time AND end_time > :start_time)
          )
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':instructor_id' => $instructorId,
        ':date' => $date,
        ':start_time' => $startTime,
        ':end_time' => $endTime
    ]);
    
    $result = $stmt->fetch();
    return ($result['conflict_count'] ?? 0) > 0;
}

/**
 * =====================================================
 * LECTURE HALL BOOKING CONFLICT CHECK
 * =====================================================
 */
function hasBookingConflict($roomId, $date, $startTime, $endTime, $excludeBookingId = null) {
    global $pdo;
    
    $sql = "
        SELECT COUNT(*) as conflict_count
        FROM lecture_hall_bookings
        WHERE room_id = :room_id
          AND booking_date = :date
          AND status = 'Confirmed'
          AND (
              (start_time < :end_time AND end_time > :start_time)
          )
    ";
    
    if ($excludeBookingId) {
        $sql .= " AND id != :exclude_id";
    }
    
    $stmt = $pdo->prepare($sql);
    $params = [
        ':room_id' => $roomId,
        ':date' => $date,
        ':start_time' => $startTime,
        ':end_time' => $endTime
    ];
    
    if ($excludeBookingId) {
        $params[':exclude_id'] = $excludeBookingId;
    }
    
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return ($result['conflict_count'] ?? 0) > 0;
}

/**
 * Get available rooms for a date/time (no conflicts)
 */
function getAvailableRooms($date, $startTime, $endTime) {
    global $pdo;
    
    $sql = "
        SELECT lr.*
        FROM lecture_rooms lr
        WHERE lr.status = 'Available'
          AND NOT EXISTS (
              SELECT 1 FROM lecture_hall_bookings bh
              WHERE bh.room_id = lr.id
                AND bh.booking_date = :date
                AND bh.status = 'Confirmed'
                AND (bh.start_time < :end_time AND bh.end_time > :start_time)
          )
        ORDER BY lr.room_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':date' => $date,
        ':start_time' => $startTime,
        ':end_time' => $endTime
    ]);
    
    return $stmt->fetchAll();
}

/**
 * =====================================================
 * HELPER: Get instructor details by user_id
 * =====================================================
 */
function getInstructorByUserId($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT i.*, u.full_name, u.email 
        FROM instructors i 
        JOIN users u ON i.user_id = u.id 
        WHERE i.user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get all active instructors (for dropdowns)
 */
function getAllActiveInstructors() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT i.id, CONCAT(u.full_name, ' (', i.employee_id, ')') as display_name,
               i.designation, ast.name as stream_name
        FROM instructors i
        JOIN users u ON i.user_id = u.id
        JOIN academic_streams ast ON i.academic_stream_id = ast.id
        WHERE i.status = 'active'
        ORDER BY u.full_name
    ");
    return $stmt->fetchAll();
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format time for display
 */
function formatTime($time) {
    if (empty($time)) return 'N/A';
    return date('h:i A', strtotime($time));
}

/**
 * Get status badge HTML (Bootstrap)
 */
function getStatusBadge($status) {
    $status = strtolower($status);
    
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        'assigned' => '<span class="badge bg-primary">Assigned</span>',
        'completed' => '<span class="badge bg-info">Completed</span>',
        'confirmed' => '<span class="badge bg-success">Confirmed</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
        'scheduled' => '<span class="badge bg-primary">Scheduled</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}
?>