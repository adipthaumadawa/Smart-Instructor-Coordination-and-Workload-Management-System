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
 * Renders a user's avatar for the topbar/menus.
 * If an uploaded avatar image URL is available, renders an <img>.
 * Otherwise falls back to the existing initial-letter badge markup
 * (the same ".avatar" span style already used across the app).
 *
 * @param string|null $avatarUrl       Absolute/app_url() image URL, or null/empty for no image.
 * @param string      $fallbackInitial Single letter (or short string) to show when there's no image.
 * @param string      $class           CSS class to apply (defaults to the existing 'avatar' style).
 */
function sic_user_avatar(?string $avatarUrl, string $fallbackInitial = 'U', string $class = 'avatar'): string {
    $class = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

    if (!empty($avatarUrl)) {
        $src = htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8');
        return '<img src="' . $src . '" alt="" class="' . $class . ' avatar-image" loading="lazy">';
    }

    $initial = htmlspecialchars(mb_strtoupper($fallbackInitial !== '' ? mb_substr($fallbackInitial, 0, 1) : 'U'), ENT_QUOTES, 'UTF-8');
    return '<span class="' . $class . '">' . $initial . '</span>';
}

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
 * GET SMART LEAVE REPLACEMENT SUGGESTIONS
 * =====================================================
 * Suggests instructors who can replace someone on leave
 * 1. Must be active instructor (not the requesting instructor)
 * 2. Not on approved leave during the period
 * 3. Preferably matching academic stream (if provided)
 * 4. Sort by lowest current workload
 */
function getSmartLeaveReplacementSuggestions($instructorId, $startDate, $endDate, $streamId = null, $limit = 5) {
    global $pdo;
    
    $suggestions = [];
    
    // Get all active instructors except the requesting instructor,
    // who are not on approved leave during the specified period
    $sql = "
        SELECT i.id, u.full_name, i.employee_id, ast.name as stream, d.name as department, i.designation,
               (SELECT COALESCE(SUM(ta.duration_hours), 0) 
                FROM task_assignments ta 
                WHERE ta.instructor_id = i.id 
                  AND ta.is_presentation_panel = 0
                  AND ta.scheduled_date BETWEEN DATE_SUB(:date_from, INTERVAL 30 DAY) AND :date_to
               ) as current_workload
        FROM instructors i
        JOIN users u ON i.user_id = u.id
        JOIN academic_streams ast ON i.academic_stream_id = ast.id
        JOIN departments d ON i.department_id = d.id
        WHERE i.status = 'active'
          AND i.id != :self_instructor_id
          AND NOT EXISTS (
              SELECT 1 FROM leave_records lr 
              WHERE lr.instructor_id = i.id 
                AND lr.status = 'Approved'
                AND NOT (lr.end_date < :start_date OR lr.start_date > :end_date)
          )
    ";
    
    $params = [
        ':self_instructor_id' => $instructorId,
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':date_from' => $startDate,
        ':date_to' => $endDate
    ];
    
    // Optional: filter by same academic stream
    if ($streamId) {
        $sql .= " AND i.academic_stream_id = :stream_id";
        $params[':stream_id'] = $streamId;
    }
    
    // Sort by workload (lowest first)
    $sql .= " ORDER BY current_workload ASC LIMIT :limit_val";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind the limit parameter
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit_val', (int)$limit, PDO::PARAM_INT);
    
    $stmt->execute();
    $instructors = $stmt->fetchAll();
    
    foreach ($instructors as $instructor) {
        $suggestions[] = [
            'instructor_id' => $instructor['id'],
            'name' => $instructor['full_name'],
            'employee_id' => $instructor['employee_id'],
            'stream' => $instructor['stream'],
            'department' => $instructor['department'],
            'current_workload' => round($instructor['current_workload'], 1),
            'designation' => $instructor['designation']
        ];
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
        'low' => '<span class="badge bg-secondary">Low</span>',
        'medium' => '<span class="badge bg-info">Medium</span>',
        'high' => '<span class="badge bg-warning text-dark">High</span>',
        'urgent' => '<span class="badge bg-danger">Urgent</span>',
        'handled' => '<span class="badge bg-success">Handled</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * =====================================================
 * TIMETABLE REQUIREMENT ASSIGNMENT (Instructor Coordinator)
 * =====================================================
 * Non-academic staff post timetable requirements (see
 * non_academic/timetable_records.php) without picking an instructor.
 * The functions below let the coordinator's page
 * (coordinator/timetable_requirements.php) auto-fill and manually fill
 * those requirements while keeping every instructor's recurring weekly
 * teaching hours as close to equal as possible.
 */

/**
 * Total recurring weekly teaching hours an instructor currently carries
 * in timetable_slots. This is the "workload" figure used to balance
 * assignments — the coordinator page always offers the least-loaded
 * eligible instructor(s) first.
 *
 * @param int         $instructorId
 * @param string|null $semester      Optional, restrict to one semester.
 * @param string|null $academicYear  Optional, restrict to one academic year.
 */
function getInstructorWeeklyHours($instructorId, $semester = null, $academicYear = null) {
    global $pdo;

    $sql = "
        SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) / 3600 AS total_seconds
        FROM timetable_slots
        WHERE instructor_id = :instructor_id
    ";
    $params = [':instructor_id' => $instructorId];

    if ($semester !== null) {
        $sql .= " AND semester = :semester";
        $params[':semester'] = $semester;
    }
    if ($academicYear !== null) {
        $sql .= " AND academic_year = :academic_year";
        $params[':academic_year'] = $academicYear;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return round((float)($result['total_seconds'] ?? 0), 2);
}

/**
 * Whether an instructor already has a recurring timetable_slots entry
 * that overlaps the given day/time. Unlike hasTimetableConflict()
 * (which checks a specific calendar date), this checks the weekly
 * recurring pattern that timetable_requirements/timetable_slots use.
 *
 * @param int         $instructorId
 * @param string      $dayOfWeek      e.g. 'Monday'
 * @param string      $startTime
 * @param string      $endTime
 * @param int|null    $excludeSlotId  Ignore this slot row (useful when re-checking after an edit).
 */
function hasWeeklyTimetableConflict($instructorId, $dayOfWeek, $startTime, $endTime, $excludeSlotId = null) {
    global $pdo;

    $sql = "
        SELECT COUNT(*) AS conflict_count
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
        $sql .= " AND id != :exclude_slot_id";
        $params[':exclude_slot_id'] = $excludeSlotId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return ($result['conflict_count'] ?? 0) > 0;
}

/**
 * Build the ranked list of instructors who are eligible to be assigned
 * to a given timetable_requirements row right now:
 *   1. Active instructors only
 *   2. Matching academic stream, if the requirement specifies one
 *   3. Not already assigned to this requirement
 *   4. No weekly timetable clash at that day/time
 *   5. Adding this slot must not push them over their max_weekly_hours
 * The result is sorted by current weekly workload (ascending), so the
 * least-loaded eligible instructor is always first — this is what both
 * the auto-assign routine and the manual "assign instructor" dropdown
 * use, which keeps workload converging towards equal over time.
 *
 * @param int $requirementId
 * @return array<int, array{id:int, display_name:string, employee_id:string, weekly_hours:float, max_weekly_hours:float, remaining_capacity:float}>
 */
function getEligibleInstructorsForRequirement($requirementId) {
    global $pdo;

    $reqStmt = $pdo->prepare("SELECT * FROM timetable_requirements WHERE id = ?");
    $reqStmt->execute([$requirementId]);
    $requirement = $reqStmt->fetch();
    if (!$requirement) {
        return [];
    }

    $slotHours = (strtotime($requirement['end_time']) - strtotime($requirement['start_time'])) / 3600;

    $sql = "
        SELECT i.id, i.employee_id, i.max_weekly_hours, u.full_name
        FROM instructors i
        JOIN users u ON i.user_id = u.id
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

    $sql .= " ORDER BY u.full_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidates = $stmt->fetchAll();

    $eligible = [];
    foreach ($candidates as $c) {
        if (hasWeeklyTimetableConflict($c['id'], $requirement['day_of_week'], $requirement['start_time'], $requirement['end_time'])) {
            continue; // already teaching/busy at that day & time
        }

        $weeklyHours = getInstructorWeeklyHours($c['id'], $requirement['semester'], $requirement['academic_year']);
        $maxHours = (float)$c['max_weekly_hours'];
        $remaining = $maxHours - $weeklyHours;

        if ($remaining < $slotHours) {
            continue; // would exceed their max weekly workload
        }

        $eligible[] = [
            'id' => (int)$c['id'],
            'display_name' => $c['full_name'] . ' (' . $c['employee_id'] . ')',
            'employee_id' => $c['employee_id'],
            'weekly_hours' => $weeklyHours,
            'max_weekly_hours' => $maxHours,
            'remaining_capacity' => round($remaining, 2),
        ];
    }

    // Least-loaded first, so both auto-assign and the manual dropdown
    // naturally steer towards equal workload across instructors.
    usort($eligible, function ($a, $b) {
        return $a['weekly_hours'] <=> $b['weekly_hours'];
    });

    return $eligible;
}

/**
 * Recomputes and saves a timetable_requirements row's status based on
 * how many instructor slots are currently filled against how many are
 * required. Call this after any insert/delete on timetable_slots that
 * touches a requirement.
 */
function refreshRequirementStatus($requirementId) {
    global $pdo;

    $reqStmt = $pdo->prepare("SELECT required_instructors FROM timetable_requirements WHERE id = ?");
    $reqStmt->execute([$requirementId]);
    $requirement = $reqStmt->fetch();
    if (!$requirement) {
        return;
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM timetable_slots WHERE requirement_id = ?");
    $countStmt->execute([$requirementId]);
    $filled = (int)$countStmt->fetch()['c'];
    $required = (int)$requirement['required_instructors'];

    if ($filled <= 0) {
        $status = 'Open';
    } elseif ($filled < $required) {
        $status = 'Partially Staffed';
    } else {
        $status = 'Fully Staffed';
    }

    $pdo->prepare("UPDATE timetable_requirements SET status = ? WHERE id = ?")->execute([$status, $requirementId]);
}

/**
 * Auto-fills the still-open seats of a timetable requirement, picking
 * the least-loaded eligible instructor(s) first so workload stays
 * balanced. Inserting into timetable_slots is what makes the pick show
 * up on the instructor's own timetable/workload pages immediately —
 * there is no separate "publish" step.
 *
 * @param int $requirementId
 * @return int Number of seats filled by this call.
 */
function autoAssignTimetableRequirement($requirementId) {
    global $pdo;

    $reqStmt = $pdo->prepare("SELECT * FROM timetable_requirements WHERE id = ?");
    $reqStmt->execute([$requirementId]);
    $requirement = $reqStmt->fetch();
    if (!$requirement) {
        return 0;
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM timetable_slots WHERE requirement_id = ?");
    $countStmt->execute([$requirementId]);
    $alreadyFilled = (int)$countStmt->fetch()['c'];
    $needed = (int)$requirement['required_instructors'] - $alreadyFilled;

    if ($needed <= 0) {
        refreshRequirementStatus($requirementId);
        return 0;
    }

    // Already sorted least-loaded first by getEligibleInstructorsForRequirement().
    $eligible = getEligibleInstructorsForRequirement($requirementId);
    $picks = array_slice($eligible, 0, $needed);

    $insertStmt = $pdo->prepare("
        INSERT INTO timetable_slots
            (instructor_id, requirement_id, day_of_week, start_time, end_time, subject, location, semester, academic_year, auto_assigned, assigned_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
    ");

    $assignedBy = $_SESSION['user_id'] ?? null;
    $filledCount = 0;

    foreach ($picks as $pick) {
        $insertStmt->execute([
            $pick['id'], $requirementId, $requirement['day_of_week'], $requirement['start_time'], $requirement['end_time'],
            $requirement['subject'], $requirement['location'], $requirement['semester'], $requirement['academic_year'],
            $assignedBy
        ]);
        $filledCount++;
    }

    refreshRequirementStatus($requirementId);

    if ($filledCount > 0 && isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'Timetable Auto-Assign', "Auto-assigned $filledCount instructor(s) to requirement #$requirementId ({$requirement['subject']})");
    }

    return $filledCount;
}

/**
 * =====================================================
 * LEAVE / REPLACEMENT HELPERS (restored after merge conflict)
 * =====================================================
 * These were present in the previous main branch but were
 * accidentally dropped when resolving a conflict by keeping
 * the other member's version of functions.php.
 * Pages that still call them: instructor/leave.php,
 * instructor/replacement_request.php, non_academic/leave_*,
 * coordinator/leave_records.php
 */

function getLeaveStatusBadge($status) {
    $map = [
        'pending'   => '<span class="badge bg-warning text-dark">Pending</span>',
        'approved'  => '<span class="badge bg-success">Confirmed</span>',
        'rejected'  => '<span class="badge bg-danger">Rejected</span>',
        'cancelled' => '<span class="badge bg-secondary">Cancelled</span>',
    ];
    $key = strtolower((string)$status);
    return $map[$key] ?? getStatusBadge($status);
}

/**
 * Notify coordinators + chief coordinators (active accounts).
 */
function sic_notify_coordinators($title, $message, $type, $relatedId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role_id IN (:coord, :chief) AND status = 'active'");
    $stmt->execute([':coord' => ROLE_COORDINATOR, ':chief' => ROLE_CHIEF_COORDINATOR]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
        createNotification($uid, $title, $message, $type, $relatedId);
    }
}

/**
 * Cancel a replacement request that is still Pending.
 *
 * Only the instructor who sent the request may cancel it, and only while the
 * other instructor has not responded. For a leave-based request the leave
 * itself is left alone (it stays Pending), so the instructor can pick a
 * different replacement or cancel the leave separately.
 *
 * @return bool true on success; on failure $message explains why.
 */
function sic_cancel_replacement_request(PDO $pdo, $requestId, $instructorId, &$message) {
    $requestId = (int)$requestId;
    $instructorId = (int)$instructorId;
    $requester = $_SESSION['full_name'] ?? 'An instructor';

    try {
        $pdo->beginTransaction();

        $sel = $pdo->prepare("
            SELECT rr.*, lr.leave_type, lr.start_date AS leave_start, lr.end_date AS leave_end
            FROM replacement_requests rr
            LEFT JOIN leave_records lr ON lr.id = rr.leave_record_id
            WHERE rr.id = ? AND rr.requested_by_instructor_id = ?
            FOR UPDATE
        ");
        $sel->execute([$requestId, $instructorId]);
        $req = $sel->fetch(PDO::FETCH_ASSOC);

        if (!$req) {
            $pdo->rollBack();
            $message = 'Replacement request not found.';
            return false;
        }
        if ($req['status'] !== 'Pending') {
            $pdo->rollBack();
            $message = 'This request has already been ' . strtolower($req['status']) . ' and can no longer be cancelled.';
            return false;
        }

        $upd = $pdo->prepare("UPDATE replacement_requests SET status = 'Cancelled' WHERE id = ? AND status = 'Pending'");
        $upd->execute([$requestId]);
        if ($upd->rowCount() === 0) {
            // The other instructor responded a moment ago.
            $pdo->rollBack();
            $message = 'This request was just answered and can no longer be cancelled.';
            return false;
        }

        $isLeave = !empty($req['leave_record_id']);
        $what = $isLeave
            ? 'to cover their ' . $req['leave_type'] . ' leave (' . formatDate($req['leave_start']) . ' to ' . formatDate($req['leave_end']) . ')'
            : 'for a task';

        if (!empty($req['suggested_instructor_id'])) {
            $u = $pdo->prepare("SELECT user_id FROM instructors WHERE id = ?");
            $u->execute([$req['suggested_instructor_id']]);
            $suggestedUserId = $u->fetchColumn();
            if ($suggestedUserId) {
                createNotification($suggestedUserId, 'Replacement Request Cancelled', "{$requester} cancelled the replacement request {$what}. No action is needed from you.", 'replacement', $requestId);
            }
        }
        // Coordinators were told about leave-based requests and requests with no
        // named replacement, so tell them it was withdrawn.
        if ($isLeave || empty($req['suggested_instructor_id'])) {
            sic_notify_coordinators('Replacement Request Cancelled', "{$requester} cancelled a replacement request {$what}.", 'replacement', $requestId);
        }

        logActivity($_SESSION['user_id'] ?? null, 'Cancel Replacement Request', "Cancelled replacement request #{$requestId}");

        $pdo->commit();
        $message = $isLeave
            ? 'Replacement request cancelled. Your leave is still pending — choose another replacement or cancel the leave.'
            : 'Replacement request cancelled.';
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('sic_cancel_replacement_request failed: ' . $e->getMessage());
        $message = 'Could not cancel the request. Please try again.';
        return false;
    }
}

/**
 * Cancel a recorded leave (Pending or confirmed/Approved).
 *
 * - Any Pending replacement request for the leave is cancelled too.
 * - If the leave was already confirmed, tasks that were handed to the
 *   replacement when it was confirmed are returned to the original instructor
 *   (only those still open and dated today or later).
 * - A leave whose end date has passed can't be cancelled.
 *
 * @return bool true on success; on failure $message explains why.
 */
function sic_cancel_leave(PDO $pdo, $leaveId, $instructorId, &$message) {
    $leaveId = (int)$leaveId;
    $instructorId = (int)$instructorId;
    $requester = $_SESSION['full_name'] ?? 'An instructor';

    try {
        $pdo->beginTransaction();

        $sel = $pdo->prepare("SELECT * FROM leave_records WHERE id = ? AND instructor_id = ? FOR UPDATE");
        $sel->execute([$leaveId, $instructorId]);
        $leave = $sel->fetch(PDO::FETCH_ASSOC);

        if (!$leave) {
            $pdo->rollBack();
            $message = 'Leave record not found.';
            return false;
        }
        if ($leave['status'] === 'Cancelled') {
            $pdo->rollBack();
            $message = 'This leave has already been cancelled.';
            return false;
        }
        if (!in_array($leave['status'], ['Pending', 'Approved'], true)) {
            $pdo->rollBack();
            $message = 'Only pending or confirmed leaves can be cancelled.';
            return false;
        }
        if (strtotime($leave['end_date']) < strtotime(date('Y-m-d'))) {
            $pdo->rollBack();
            $message = 'This leave has already ended and can no longer be cancelled.';
            return false;
        }

        $wasApproved = ($leave['status'] === 'Approved');
        $period = formatDate($leave['start_date']) . ' to ' . formatDate($leave['end_date']);

        $pdo->prepare("UPDATE leave_records SET status = 'Cancelled' WHERE id = ?")->execute([$leaveId]);

        // 1) Withdraw any request still waiting on a reply.
        $pendingStmt = $pdo->prepare("SELECT id, suggested_instructor_id FROM replacement_requests WHERE leave_record_id = ? AND status = 'Pending' FOR UPDATE");
        $pendingStmt->execute([$leaveId]);
        $pendingReqs = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
        $cancelReq = $pdo->prepare("UPDATE replacement_requests SET status = 'Cancelled' WHERE id = ? AND status = 'Pending'");
        $userLookup = $pdo->prepare("SELECT user_id FROM instructors WHERE id = ?");
        foreach ($pendingReqs as $pr) {
            $cancelReq->execute([$pr['id']]);
            if (!empty($pr['suggested_instructor_id'])) {
                $userLookup->execute([$pr['suggested_instructor_id']]);
                $uid = $userLookup->fetchColumn();
                if ($uid) {
                    createNotification($uid, 'Replacement Request Cancelled', "{$requester} cancelled their leave ({$period}), so the request for you to cover it is withdrawn.", 'replacement', $pr['id']);
                }
            }
        }

        // 2) If it was confirmed, hand the reassigned tasks back and tell the replacement.
        $returned = 0;
        if ($wasApproved) {
            $tasks = $pdo->prepare("
                SELECT ltr.id, ltr.task_assignment_id, ltr.from_instructor_id
                FROM leave_task_reassignments ltr
                JOIN task_assignments ta ON ta.id = ltr.task_assignment_id
                WHERE ltr.leave_record_id = ?
                  AND ta.instructor_id = ltr.to_instructor_id
                  AND ta.status IN ('Assigned','Accepted')
                  AND ta.scheduled_date >= CURDATE()
                FOR UPDATE
            ");
            $tasks->execute([$leaveId]);
            $giveBack = $pdo->prepare("UPDATE task_assignments SET instructor_id = ? WHERE id = ?");
            foreach ($tasks->fetchAll(PDO::FETCH_ASSOC) as $t) {
                $giveBack->execute([$t['from_instructor_id'], $t['task_assignment_id']]);
                $returned++;
            }

            $acc = $pdo->prepare("SELECT suggested_instructor_id FROM replacement_requests WHERE leave_record_id = ? AND status = 'Accepted' ORDER BY id DESC LIMIT 1");
            $acc->execute([$leaveId]);
            $accInstructor = $acc->fetchColumn();
            if ($accInstructor) {
                $userLookup->execute([$accInstructor]);
                $uid = $userLookup->fetchColumn();
                if ($uid) {
                    $extra = $returned > 0 ? " {$returned} task(s) have been returned to them." : '';
                    createNotification($uid, 'Leave Cancelled', "{$requester} cancelled their leave ({$period}) that you agreed to cover. You no longer need to cover it.{$extra}", 'leave', $leaveId);
                }
            }
        }

        // 3) Visibility for the people who were told about the leave.
        sic_notify_coordinators('Leave Cancelled', "{$requester} cancelled their {$leave['leave_type']} leave ({$period}).", 'leave', $leaveId);
        $na = $pdo->prepare("SELECT id FROM users WHERE role_id = :na AND status = 'active'");
        $na->execute([':na' => ROLE_NON_ACADEMIC]);
        foreach ($na->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            createNotification($uid, 'Instructor Leave Cancelled', "{$requester} cancelled their {$leave['leave_type']} leave ({$period}).", 'leave', $leaveId);
        }

        logActivity($_SESSION['user_id'] ?? null, 'Cancel Leave', "Cancelled leave #{$leaveId} ({$period})" . ($returned ? "; {$returned} task(s) returned" : ''));

        $pdo->commit();
        $message = 'Leave cancelled.' . ($returned > 0 ? " {$returned} task(s) were returned to you." : '');
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('sic_cancel_leave failed: ' . $e->getMessage());
        $message = 'Could not cancel the leave. Please try again.';
        return false;
    }
}