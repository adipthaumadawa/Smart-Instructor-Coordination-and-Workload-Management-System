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
 * =====================================================
 * CSRF PROTECTION
 * =====================================================
 * Generates/verifies a per-session token so state-changing
 * requests (POST forms, accept/reject actions) can't be
 * forged by a third-party site or replayed link.
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verifies the token submitted with a request. Stops execution
 * with a 403 response if it is missing or does not match.
 */
function csrf_verify() {
    $submitted = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !is_string($submitted) || !hash_equals($_SESSION['csrf_token'], $submitted)) {
        http_response_code(403);
        die('Your session security token has expired or is invalid. Please go back, refresh the page, and try again.');
    }
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
 * =====================================================
 * SMART LEAVE REPLACEMENT SUGGESTION ALGORITHM
 * =====================================================
 * Used when an instructor records a leave and must pick another
 * instructor to cover for them during the whole leave period
 * (not just one task). Simple, explainable algorithm:
 * 1. Must be active, and not the instructor who is going on leave
 * 2. Not already on their own approved leave that overlaps the period
 * 3. Preferably matching academic stream (shown first, if provided)
 * 4. Sorted by lowest workload during that period
 */
function getSmartLeaveReplacementSuggestions($excludeInstructorId, $startDate, $endDate, $streamId = null, $limit = 8) {
    global $pdo;

    $sql = "
        SELECT i.*, u.full_name, ast.name as stream_name, d.name as dept_name,
               (SELECT COALESCE(SUM(ta.duration_hours), 0)
                FROM task_assignments ta
                WHERE ta.instructor_id = i.id
                  AND ta.is_presentation_panel = 0
                  AND ta.status IN ('Assigned','Accepted','Completed')
                  AND ta.scheduled_date BETWEEN :period_from AND :period_to
               ) as period_workload
        FROM instructors i
        JOIN users u ON i.user_id = u.id
        JOIN academic_streams ast ON i.academic_stream_id = ast.id
        JOIN departments d ON i.department_id = d.id
        WHERE i.status = 'active'
          AND i.id != :exclude_id
          AND NOT EXISTS (
              SELECT 1 FROM leave_records lr
              WHERE lr.instructor_id = i.id
                AND lr.status = 'Approved'
                AND lr.start_date <= :period_to2
                AND lr.end_date >= :period_from2
          )
    ";

    $params = [
        ':period_from' => $startDate,
        ':period_to' => $endDate,
        ':exclude_id' => $excludeInstructorId,
        ':period_to2' => $endDate,
        ':period_from2' => $startDate,
    ];

    if ($streamId) {
        $sql .= " AND i.academic_stream_id = :stream_id";
        $params[':stream_id'] = $streamId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $instructors = $stmt->fetchAll();

    // Rank: same stream first (if a stream was given), then lowest workload
    usort($instructors, function ($a, $b) use ($streamId) {
        if ($streamId) {
            $aMatch = ((int)$a['academic_stream_id'] === (int)$streamId) ? 0 : 1;
            $bMatch = ((int)$b['academic_stream_id'] === (int)$streamId) ? 0 : 1;
            if ($aMatch !== $bMatch) {
                return $aMatch <=> $bMatch;
            }
        }
        return $a['period_workload'] <=> $b['period_workload'];
    });

    $suggestions = [];
    foreach ($instructors as $instructor) {
        $suggestions[] = [
            'instructor_id' => $instructor['id'],
            'name' => $instructor['full_name'],
            'employee_id' => $instructor['employee_id'],
            'stream' => $instructor['stream_name'],
            'department' => $instructor['dept_name'],
            'current_workload' => round($instructor['period_workload'], 1),
            'designation' => $instructor['designation'],
        ];
        if (count($suggestions) >= $limit) {
            break;
        }
    }

    return $suggestions;
}

/**
 * =====================================================
 * TIMETABLE REQUIREMENTS: AUTO-ASSIGNMENT
 * =====================================================
 * Non-academic staff post a semester timetable requirement
 * (day/time/subject/location, how many instructors it needs,
 * optionally which academic stream it belongs to). These
 * functions pick instructors for it automatically, and let
 * the coordinator adjust the result afterwards.
 */

/**
 * Total weekly recurring teaching hours an instructor already
 * has on the timetable (used to rank candidates by workload —
 * separate from calculateWorkload(), which is for dated,
 * one-off task_assignments, not the recurring weekly timetable).
 */
function getWeeklyTeachingHours($instructorId) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) / 3600 AS total_hours
        FROM timetable_slots
        WHERE instructor_id = :instructor_id
    ");
    $stmt->execute([':instructor_id' => $instructorId]);
    $result = $stmt->fetch();
    return (float)($result['total_hours'] ?? 0);
}

/**
 * Check if an instructor already has a recurring timetable slot
 * that overlaps this day/time (weekly conflict, not date-based —
 * use hasTimetableConflict() instead for a specific calendar date).
 */
function hasWeeklyTimetableConflict($instructorId, $dayOfWeek, $startTime, $endTime, $excludeSlotId = null) {
    global $pdo;

    $sql = "
        SELECT COUNT(*) as conflict_count
        FROM timetable_slots
        WHERE instructor_id = :instructor_id
          AND day_of_week = :day_of_week
          AND (start_time < :end_time AND end_time > :start_time)
    ";
    $params = [
        ':instructor_id' => $instructorId,
        ':day_of_week' => $dayOfWeek,
        ':start_time' => $startTime,
        ':end_time' => $endTime
    ];
    if ($excludeSlotId) {
        $sql .= " AND id != :exclude_id";
        $params[':exclude_id'] = $excludeSlotId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return ($result['conflict_count'] ?? 0) > 0;
}

/**
 * Ranked list of instructors eligible to fill a timetable requirement:
 * 1. Must be active (not on_leave / inactive)
 * 2. Matching academic stream, if the requirement specifies one
 * 3. Not already assigned to this requirement
 * 4. No weekly timetable conflict with the requirement's day/time
 * 5. Sorted by lowest current weekly teaching hours first
 */
function getEligibleInstructorsForRequirement($requirementId, $limit = null) {
    global $pdo;

    $reqStmt = $pdo->prepare("SELECT * FROM timetable_requirements WHERE id = ?");
    $reqStmt->execute([$requirementId]);
    $requirement = $reqStmt->fetch();
    if (!$requirement) return [];

    $sql = "
        SELECT i.*, u.full_name, ast.name as stream_name
        FROM instructors i
        JOIN users u ON i.user_id = u.id
        JOIN academic_streams ast ON i.academic_stream_id = ast.id
        WHERE i.status = 'active'
          AND i.id NOT IN (
              SELECT instructor_id FROM timetable_slots WHERE requirement_id = :requirement_id
          )
    ";
    $params = [':requirement_id' => $requirementId];

    if (!empty($requirement['academic_stream_id'])) {
        $sql .= " AND i.academic_stream_id = :stream_id";
        $params[':stream_id'] = $requirement['academic_stream_id'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidates = $stmt->fetchAll();

    $eligible = [];
    foreach ($candidates as $c) {
        if (hasWeeklyTimetableConflict($c['id'], $requirement['day_of_week'], $requirement['start_time'], $requirement['end_time'])) {
            continue;
        }
        $c['weekly_hours'] = getWeeklyTeachingHours($c['id']);
        $eligible[] = $c;
    }

    usort($eligible, fn($a, $b) => $a['weekly_hours'] <=> $b['weekly_hours']);

    if ($limit) {
        $eligible = array_slice($eligible, 0, $limit);
    }
    return $eligible;
}

/**
 * Recompute and store a requirement's status based on how many
 * instructors are currently assigned to it vs how many it needs.
 */
function refreshRequirementStatus($requirementId) {
    global $pdo;

    $reqStmt = $pdo->prepare("SELECT required_instructors FROM timetable_requirements WHERE id = ?");
    $reqStmt->execute([$requirementId]);
    $requirement = $reqStmt->fetch();
    if (!$requirement) return;

    $countStmt = $pdo->prepare("SELECT COUNT(*) as c FROM timetable_slots WHERE requirement_id = ?");
    $countStmt->execute([$requirementId]);
    $assigned = (int)$countStmt->fetch()['c'];
    $required = (int)$requirement['required_instructors'];

    if ($assigned <= 0) {
        $status = 'Open';
    } elseif ($assigned < $required) {
        $status = 'Partially Staffed';
    } else {
        $status = 'Fully Staffed';
    }

    $pdo->prepare("UPDATE timetable_requirements SET status = ? WHERE id = ?")->execute([$status, $requirementId]);
    return $status;
}

/**
 * Automatically fill a requirement with the best-ranked eligible
 * instructors until required_instructors is met (or candidates run out).
 * Called right after non-academic staff post a requirement, and can be
 * re-run by the coordinator later to try to fill any still-open seats.
 * Returns the number of instructors newly assigned.
 */
function autoAssignTimetableRequirement($requirementId) {
    global $pdo;

    $reqStmt = $pdo->prepare("SELECT * FROM timetable_requirements WHERE id = ?");
    $reqStmt->execute([$requirementId]);
    $requirement = $reqStmt->fetch();
    if (!$requirement) return 0;

    $countStmt = $pdo->prepare("SELECT COUNT(*) as c FROM timetable_slots WHERE requirement_id = ?");
    $countStmt->execute([$requirementId]);
    $alreadyAssigned = (int)$countStmt->fetch()['c'];
    $stillNeeded = (int)$requirement['required_instructors'] - $alreadyAssigned;
    if ($stillNeeded <= 0) {
        refreshRequirementStatus($requirementId);
        return 0;
    }

    $candidates = getEligibleInstructorsForRequirement($requirementId, $stillNeeded);

    $insert = $pdo->prepare("
        INSERT INTO timetable_slots
            (instructor_id, requirement_id, day_of_week, start_time, end_time, subject, location, semester, academic_year, auto_assigned, assigned_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NULL)
    ");

    $newlyAssigned = 0;
    foreach ($candidates as $candidate) {
        $insert->execute([
            $candidate['id'],
            $requirementId,
            $requirement['day_of_week'],
            $requirement['start_time'],
            $requirement['end_time'],
            $requirement['subject'],
            $requirement['location'],
            $requirement['semester'],
            $requirement['academic_year']
        ]);
        $newlyAssigned++;
    }

    refreshRequirementStatus($requirementId);
    return $newlyAssigned;
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
        'low' => '<span class="badge bg-secondary">Low</span>',
        'medium' => '<span class="badge bg-info">Medium</span>',
        'high' => '<span class="badge bg-warning text-dark">High</span>',
        'urgent' => '<span class="badge bg-danger">Urgent</span>',
        'handled' => '<span class="badge bg-success">Handled</span>',
        'open' => '<span class="badge bg-danger">Open</span>',
        'partially staffed' => '<span class="badge bg-warning text-dark">Partially Staffed</span>',
        'fully staffed' => '<span class="badge bg-success">Fully Staffed</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Status badge specifically for leave_records rows.
 * A leave's 'Approved' status is only ever set once the chosen
 * replacement instructor has accepted, so it is shown as "Confirmed"
 * here to match how the workflow is described to instructors.
 */
function getLeaveStatusBadge($status) {
    if (strtolower($status) === 'approved') {
        return '<span class="badge bg-success">Confirmed</span>';
    }
    return getStatusBadge($status);
}
?>