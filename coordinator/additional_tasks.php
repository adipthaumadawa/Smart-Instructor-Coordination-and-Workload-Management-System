<?php
/**
 * Coordinator - Additional Task Requests
 * Smart Instructor Coordination and Workload Management System
 *
 * Flow:
 * coordinator enters the task details and searches for available
 * instructors for that date/time.
 *
 * Selecting an instructor from the results immediately creates
 * the task request AND assigns it to that instructor in one step.
 *
 * This page also supports:
 * - Updating existing task requests
 * - Deleting existing task requests
 * - Deleting related task assignments
 * - Conflict checking when updating an assigned task
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Additional Tasks";

$allowedUrgency = [
    'Low',
    'Medium',
    'High',
    'Urgent'
];


// ================================================================
// PREFERRED INSTRUCTOR
// ================================================================

$preferredInstructorId = (int)(
    $_GET['preferred_instructor_id']
    ?? $_GET['instructor_id']
    ?? 0
);

$preferredInstructorName = trim(
    (string)(
        $_GET['instructor_name']
        ?? ''
    )
);


// ================================================================
// VALIDATE TASK FIELDS
// ================================================================

/**
 * Validates the shared task-detail fields.
 *
 * Used by:
 * - Search Available Instructors
 * - Update Task Request
 * - Assign Instructor
 *
 * Returns:
 * [
 *     errors[],
 *     cleanValues[]
 * ]
 */
function validateTaskFields(
    array $data,
    PDO $pdo
): array {

    global $allowedUrgency;

    $errors = [];


    $title = trim(
        (string)($data['title'] ?? '')
    );

    $description = trim(
        (string)($data['description'] ?? '')
    );

    $taskTypeId = (int)(
        $data['task_type_id']
        ?? 0
    );

    $preferredDate = trim(
        (string)(
            $data['preferred_date']
            ?? ''
        )
    );

    $startTime = trim(
        (string)(
            $data['start_time']
            ?? ''
        )
    );

    $endTime = trim(
        (string)(
            $data['end_time']
            ?? ''
        )
    );

    $location = trim(
        (string)(
            $data['location']
            ?? ''
        )
    );

    $urgency = trim(
        (string)(
            $data['urgency']
            ?? ''
        )
    );

    $streamId = (int)(
        $data['stream_id']
        ?? 0
    );

    $durationInput =
        $data['duration_hours']
        ?? '';


    // ------------------------------------------------------------
    // TITLE
    // ------------------------------------------------------------

    if (
        $title === ''
        || mb_strlen($title) > 150
    ) {

        $errors[] =
            'Task title is required and must be under 150 characters.';
    }


    // ------------------------------------------------------------
    // TASK TYPE
    // ------------------------------------------------------------

    if ($taskTypeId <= 0) {

        $errors[] =
            'Please select a task type.';
    }


    // ------------------------------------------------------------
    // DATE
    // ------------------------------------------------------------

    if (
        $preferredDate === ''
        || !DateTime::createFromFormat(
            'Y-m-d',
            $preferredDate
        )
    ) {

        $errors[] =
            'A valid preferred date is required.';
    }


    // ------------------------------------------------------------
    // START TIME
    // ------------------------------------------------------------

    if (
        $startTime === ''
        || !DateTime::createFromFormat(
            'H:i',
            $startTime
        )
    ) {

        $errors[] =
            'A valid start time is required.';
    }


    // ------------------------------------------------------------
    // END TIME
    // ------------------------------------------------------------

    if (
        $endTime === ''
        || !DateTime::createFromFormat(
            'H:i',
            $endTime
        )
    ) {

        $errors[] =
            'A valid end time is required.';
    }


    // ------------------------------------------------------------
    // URGENCY
    // ------------------------------------------------------------

    if (
        !in_array(
            $urgency,
            $allowedUrgency,
            true
        )
    ) {

        $errors[] =
            'Please select a valid urgency level.';
    }


    // ------------------------------------------------------------
    // TIME ORDER
    // ------------------------------------------------------------

    if (
        empty($errors)
        && strtotime($endTime)
            <= strtotime($startTime)
    ) {

        $errors[] =
            'End time must be after start time.';
    }


    // ------------------------------------------------------------
    // TASK TYPE VALIDATION
    // ------------------------------------------------------------

    if (empty($errors)) {

        $checkType = $pdo->prepare("
            SELECT id
            FROM task_types
            WHERE id = ?
              AND is_presentation = 0
        ");

        $checkType->execute([
            $taskTypeId
        ]);


        if (!$checkType->fetch()) {

            $errors[] =
                'The selected task type is not valid.';
        }
    }


    // ------------------------------------------------------------
    // DURATION
    // ------------------------------------------------------------

    $duration = 2.0;


    if (empty($errors)) {

        $duration = round(
            (
                strtotime($endTime)
                - strtotime($startTime)
            ) / 3600,
            2
        );


        if ($duration <= 0) {

            $duration =
                (float)$durationInput ?: 2;
        }


        if (
            $duration <= 0
            || $duration > 24
        ) {

            $duration = 2;
        }
    }


    return [
        $errors,

        [
            'title' =>
                $title,

            'description' =>
                $description,

            'task_type_id' =>
                $taskTypeId,

            'preferred_date' =>
                $preferredDate,

            'start_time' =>
                $startTime,

            'end_time' =>
                $endTime,

            'location' =>
                $location,

            'urgency' =>
                $urgency,

            'stream_id' =>
                $streamId,

            'duration' =>
                $duration,

            'duration_hours' =>
                $duration
        ]
    ];
}


// ================================================================
// INITIAL VALUES
// ================================================================

$formErrors = [];

$clean = [];

$suggestions = [];

$searched = false;

$editTask = null;


// ================================================================
// DELETE TASK REQUEST
// ================================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (
        $_POST['action'] ?? ''
    ) === 'delete_task_request'
) {

    csrf_verify();


    $requestId = (int)(
        $_POST['request_id']
        ?? 0
    );


    if ($requestId <= 0) {

        $_SESSION['form_errors'] = [
            'Invalid task request.'
        ];

        header(
            'Location: additional_tasks.php'
        );

        exit;
    }


    try {

        $pdo->beginTransaction();


        // --------------------------------------------------------
        // Get task before deleting it
        // --------------------------------------------------------

        $stmt = $pdo->prepare("
            SELECT
                id,
                title
            FROM additional_task_requests
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $requestId
        ]);


        $taskToDelete =
            $stmt->fetch();


        if (!$taskToDelete) {

            $pdo->rollBack();


            $_SESSION['form_errors'] = [
                'Task request not found.'
            ];


            header(
                'Location: additional_tasks.php'
            );

            exit;
        }


        // --------------------------------------------------------
        // Delete related assignments first
        // --------------------------------------------------------

        $deleteAssignments = $pdo->prepare("
            DELETE FROM task_assignments
            WHERE additional_task_request_id = ?
        ");

        $deleteAssignments->execute([
            $requestId
        ]);


        // --------------------------------------------------------
        // Delete the task request
        // --------------------------------------------------------

        $deleteRequest = $pdo->prepare("
            DELETE FROM additional_task_requests
            WHERE id = ?
        ");

        $deleteRequest->execute([
            $requestId
        ]);


        $pdo->commit();


        // --------------------------------------------------------
        // Activity log
        // --------------------------------------------------------

        logActivity(
            $_SESSION['user_id'],
            'Delete Additional Task Request',
            "Deleted task request #{$requestId} ({$taskToDelete['title']})"
        );


        $_SESSION['success'] =
            'Task request deleted successfully.';


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();
        }


        error_log(
            'Additional task deletion failed: '
            . $e->getMessage()
        );


        $_SESSION['form_errors'] = [
            'Unable to delete the task request. Please try again.'
        ];
    }


    header(
        'Location: additional_tasks.php'
    );

    exit;
}


// ================================================================
// UPDATE TASK REQUEST
// ================================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (
        $_POST['action'] ?? ''
    ) === 'update_task_request'
) {

    csrf_verify();


    $requestId = (int)(
        $_POST['request_id']
        ?? 0
    );


    if ($requestId <= 0) {

        $_SESSION['form_errors'] = [
            'Invalid task request.'
        ];


        header(
            'Location: additional_tasks.php'
        );

        exit;
    }


    // ------------------------------------------------------------
    // Validate edited values
    // ------------------------------------------------------------

    [
        $updateErrors,
        $updateClean
    ] = validateTaskFields(
        $_POST,
        $pdo
    );


    if (!empty($updateErrors)) {

        $_SESSION['form_errors'] =
            $updateErrors;

        $_SESSION['old_input'] =
            $_POST;


        header(
            'Location: additional_tasks.php?edit_id='
            . $requestId
        );

        exit;
    }


    try {

        $pdo->beginTransaction();


        // --------------------------------------------------------
        // Get existing task + assignment
        // --------------------------------------------------------

        $existingStmt = $pdo->prepare("
            SELECT
                atr.*,
                ta.id AS assignment_id,
                ta.instructor_id,
                i.user_id AS instructor_user_id,
                u.full_name AS instructor_name
            FROM additional_task_requests atr

            LEFT JOIN task_assignments ta
                ON ta.additional_task_request_id = atr.id

            LEFT JOIN instructors i
                ON ta.instructor_id = i.id

            LEFT JOIN users u
                ON i.user_id = u.id

            WHERE atr.id = ?

            LIMIT 1
        ");


        $existingStmt->execute([
            $requestId
        ]);


        $existingTask =
            $existingStmt->fetch();


        if (!$existingTask) {

            throw new Exception(
                'Task request not found.'
            );
        }


        // --------------------------------------------------------
        // Check instructor conflict
        // --------------------------------------------------------

        if (
            !empty(
                $existingTask['assignment_id']
            )
            && !empty(
                $existingTask['instructor_id']
            )
        ) {


            // ----------------------------------------------------
            // Task conflict
            // ----------------------------------------------------

            $conflictStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM task_assignments
                WHERE instructor_id = ?

                  AND id <> ?

                  AND scheduled_date = ?

                  AND start_time < ?

                  AND end_time > ?

                  AND status NOT IN (
                      'Cancelled',
                      'Rejected'
                  )
            ");


            $conflictStmt->execute([

                $existingTask['instructor_id'],

                $existingTask['assignment_id'],

                $updateClean['preferred_date'],

                $updateClean['end_time'],

                $updateClean['start_time']
            ]);


            $taskConflict =
                (int)$conflictStmt->fetchColumn();


            if ($taskConflict > 0) {

                throw new Exception(
                    'The assigned instructor has another task during the selected time.'
                );
            }


            // ----------------------------------------------------
            // Timetable conflict
            // ----------------------------------------------------

            if (
                hasTimetableConflict(
                    $existingTask['instructor_id'],
                    $updateClean['preferred_date'],
                    $updateClean['start_time'],
                    $updateClean['end_time']
                )
            ) {

                throw new Exception(
                    'The assigned instructor has a timetable conflict during the selected time.'
                );
            }
        }


        // --------------------------------------------------------
        // Update additional_task_requests
        // --------------------------------------------------------

        $updateRequest = $pdo->prepare("
            UPDATE additional_task_requests

            SET
                title = ?,
                description = ?,
                task_type_id = ?,
                preferred_date = ?,
                start_time = ?,
                end_time = ?,
                duration_hours = ?,
                location = ?,
                urgency = ?

            WHERE id = ?
        ");


        $updateRequest->execute([

            $updateClean['title'],

            $updateClean['description'],

            $updateClean['task_type_id'],

            $updateClean['preferred_date'],

            $updateClean['start_time'],

            $updateClean['end_time'],

            $updateClean['duration'],

            $updateClean['location'],

            $updateClean['urgency'],

            $requestId
        ]);


        // --------------------------------------------------------
        // Update task assignment
        // --------------------------------------------------------

        if (
            !empty(
                $existingTask['assignment_id']
            )
        ) {

            $updateAssignment = $pdo->prepare("
                UPDATE task_assignments

                SET
                    task_type_id = ?,
                    scheduled_date = ?,
                    start_time = ?,
                    end_time = ?,
                    duration_hours = ?,
                    location = ?

                WHERE id = ?
            ");


            $updateAssignment->execute([

                $updateClean['task_type_id'],

                $updateClean['preferred_date'],

                $updateClean['start_time'],

                $updateClean['end_time'],

                $updateClean['duration'],

                $updateClean['location'],

                $existingTask['assignment_id']
            ]);
        }


        $pdo->commit();


        // --------------------------------------------------------
        // Notify instructor
        // --------------------------------------------------------

        if (
            !empty(
                $existingTask['instructor_user_id']
            )
        ) {

            createNotification(

                $existingTask[
                    'instructor_user_id'
                ],

                'Task Updated',

                "The task '{$updateClean['title']}' has been updated to "
                . formatDate(
                    $updateClean['preferred_date']
                )
                . ' ('
                . formatTime(
                    $updateClean['start_time']
                )
                . ' - '
                . formatTime(
                    $updateClean['end_time']
                )
                . ').',

                'task',

                $requestId
            );
        }


        // --------------------------------------------------------
        // Activity log
        // --------------------------------------------------------

        logActivity(

            $_SESSION['user_id'],

            'Update Additional Task Request',

            "Updated task request #{$requestId} ({$updateClean['title']})"
        );


        $_SESSION['success'] =
            'Task request updated successfully.';


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();
        }


        error_log(
            'Additional task update failed: '
            . $e->getMessage()
        );


        $_SESSION['form_errors'] = [
            $e->getMessage()
        ];
    }


    header(
        'Location: additional_tasks.php'
    );

    exit;
}


// ================================================================
// ASSIGN SELECTED INSTRUCTOR
// ================================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset(
        $_POST['assign_instructor_id']
    )
) {

    csrf_verify();


    [
        $formErrors,
        $clean
    ] = validateTaskFields(
        $_POST,
        $pdo
    );


    $instructorId = (int)(
        $_POST['assign_instructor_id']
    );


    $instructor = null;


    if ($instructorId <= 0) {

        $formErrors[] =
            'Please select an instructor to assign.';

    } elseif (empty($formErrors)) {


        $instrStmt = $pdo->prepare("
            SELECT
                i.id,
                i.user_id,
                u.full_name
            FROM instructors i
            JOIN users u
                ON i.user_id = u.id
            WHERE i.id = ?
              AND i.status = 'active'
        ");


        $instrStmt->execute([
            $instructorId
        ]);


        $instructor =
            $instrStmt->fetch();


        if (!$instructor) {

            $formErrors[] =
                'The selected instructor is no longer available.';

        } elseif (
            hasTimetableConflict(
                $instructorId,
                $clean['preferred_date'],
                $clean['start_time'],
                $clean['end_time']
            )
            ||
            hasTaskConflict(
                $instructorId,
                $clean['preferred_date'],
                $clean['start_time'],
                $clean['end_time']
            )
        ) {

            $formErrors[] =
                'That instructor was just booked for a conflicting task. Please search again.';
        }
    }


    if (empty($formErrors)) {

        try {

            $pdo->beginTransaction();


            // ----------------------------------------------------
            // Create task request
            // ----------------------------------------------------

            $stmt = $pdo->prepare("
                INSERT INTO additional_task_requests
                (
                    title,
                    description,
                    task_type_id,
                    requested_by,
                    preferred_date,
                    start_time,
                    end_time,
                    duration_hours,
                    location,
                    urgency,
                    status,
                    created_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'Assigned',
                    NOW()
                )
            ");


            $stmt->execute([

                $clean['title'],

                $clean['description'],

                $clean['task_type_id'],

                $_SESSION['user_id'],

                $clean['preferred_date'],

                $clean['start_time'],

                $clean['end_time'],

                $clean['duration'],

                $clean['location'],

                $clean['urgency']
            ]);


            $requestId =
                (int)$pdo->lastInsertId();


            // ----------------------------------------------------
            // Create assignment
            // ----------------------------------------------------

            $stmt2 = $pdo->prepare("
                INSERT INTO task_assignments
                (
                    additional_task_request_id,
                    task_type_id,
                    instructor_id,
                    assigned_by,
                    assignment_date,
                    scheduled_date,
                    start_time,
                    end_time,
                    duration_hours,
                    location,
                    status,
                    created_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    CURDATE(),
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'Assigned',
                    NOW()
                )
            ");


            $stmt2->execute([

                $requestId,

                $clean['task_type_id'],

                $instructorId,

                $_SESSION['user_id'],

                $clean['preferred_date'],

                $clean['start_time'],

                $clean['end_time'],

                $clean['duration'],

                $clean['location']
            ]);


            $pdo->commit();


            // ----------------------------------------------------
            // Notify instructor
            // ----------------------------------------------------

            createNotification(

                $instructor['user_id'],

                'New Task Assigned',

                "You've been assigned: {$clean['title']} on "
                . formatDate(
                    $clean['preferred_date']
                )
                . ' ('
                . formatTime(
                    $clean['start_time']
                )
                . ' - '
                . formatTime(
                    $clean['end_time']
                )
                . ').',

                'task',

                $requestId
            );


            // ----------------------------------------------------
            // Activity log
            // ----------------------------------------------------

            logActivity(

                $_SESSION['user_id'],

                'Assign Additional Task',

                "Assigned '{$clean['title']}' to {$instructor['full_name']}"
            );


            $_SESSION['success'] =
                "Task assigned to {$instructor['full_name']} and they've been notified.";


            header(
                'Location: additional_tasks.php'
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }


            error_log(
                'Additional task assignment failed: '
                . $e->getMessage()
            );


            $formErrors[] =
                'Something went wrong while assigning the task. Please try again.';
        }
    }


    $_SESSION['form_errors'] =
        $formErrors;

    $_SESSION['old_input'] =
        $_POST;


    header(
        'Location: additional_tasks.php'
    );

    exit;
}


// ================================================================
// SEARCH AVAILABLE INSTRUCTORS
// ================================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['title'])
) {

    $searched = true;


    [
        $formErrors,
        $clean
    ] = validateTaskFields(
        $_GET,
        $pdo
    );


    $preferredInstructorId =
        (int)(
            $_GET['preferred_instructor_id']
            ?? $preferredInstructorId
        );


    if (empty($formErrors)) {

        $suggestions =
            getSmartSuggestions(

                $clean['task_type_id']
                    ?: null,

                $clean['preferred_date'],

                $clean['start_time'],

                $clean['end_time'],

                $clean['stream_id']
                    ?: null,

                8
            );


        // --------------------------------------------------------
        // Put preferred instructor first
        // --------------------------------------------------------

        if (
            $preferredInstructorId > 0
            && !empty($suggestions)
        ) {

            usort(
                $suggestions,
                function (
                    $a,
                    $b
                ) use (
                    $preferredInstructorId
                ) {

                    $aMatch =
                        (
                            (int)$a['instructor_id']
                            ===
                            $preferredInstructorId
                        )
                        ? 0
                        : 1;


                    $bMatch =
                        (
                            (int)$b['instructor_id']
                            ===
                            $preferredInstructorId
                        )
                        ? 0
                        : 1;


                    return $aMatch <=> $bMatch;
                }
            );
        }
    }
}


// ================================================================
// SESSION ERROR / OLD INPUT
// ================================================================

if (empty($clean)) {

    $formErrors =
        $_SESSION['form_errors']
        ?? $formErrors;


    $clean =
        $_SESSION['old_input']
        ?? [];
}


unset(
    $_SESSION['form_errors'],
    $_SESSION['old_input']
);


// ================================================================
// LOAD TASK FOR EDITING
// ================================================================

$editTaskId = (int)(
    $_GET['edit_id']
    ?? 0
);


if ($editTaskId > 0) {

    $editStmt = $pdo->prepare("
        SELECT
            atr.*,
            ta.id AS assignment_id,
            i.id AS instructor_id,
            iu.full_name AS assigned_instructor_name
        FROM additional_task_requests atr

        LEFT JOIN task_assignments ta
            ON ta.additional_task_request_id = atr.id

        LEFT JOIN instructors i
            ON ta.instructor_id = i.id

        LEFT JOIN users iu
            ON i.user_id = iu.id

        WHERE atr.id = ?

        LIMIT 1
    ");


    $editStmt->execute([
        $editTaskId
    ]);


    $editTask =
        $editStmt->fetch();


    if ($editTask) {

        /*
         * Load existing task values into the
         * same variables used by the form.
         */
        $clean = [

            'title' =>
                $editTask['title'],

            'description' =>
                $editTask['description'],

            'task_type_id' =>
                $editTask['task_type_id'],

            'preferred_date' =>
                $editTask['preferred_date'],

            'start_time' =>
                substr(
                    $editTask['start_time'],
                    0,
                    5
                ),

            'end_time' =>
                substr(
                    $editTask['end_time'],
                    0,
                    5
                ),

            'location' =>
                $editTask['location'],

            'urgency' =>
                $editTask['urgency'],

            'duration' =>
                $editTask['duration_hours'],

            'duration_hours' =>
                $editTask['duration_hours'],

            /*
             * Stream is not stored in the task request,
             * therefore leave it empty during editing.
             */
            'stream_id' =>
                ''
        ];
    }
}


// ================================================================
// LOAD TASK TYPES / STREAMS / TASK REQUESTS
// ================================================================

$taskTypes = $pdo->query("
    SELECT *
    FROM task_types
    WHERE is_presentation = 0
    ORDER BY name
")->fetchAll();


$streams = $pdo->query("
    SELECT *
    FROM academic_streams
    ORDER BY name
")->fetchAll();


$tasks = $pdo->query("
    SELECT
        atr.*,

        u.full_name AS requested_by_name,

        tt.name AS task_type,

        iu.full_name AS assigned_instructor_name

    FROM additional_task_requests atr

    JOIN users u
        ON atr.requested_by = u.id

    JOIN task_types tt
        ON atr.task_type_id = tt.id

    LEFT JOIN task_assignments ta
        ON ta.additional_task_request_id = atr.id

    LEFT JOIN instructors i
        ON ta.instructor_id = i.id

    LEFT JOIN users iu
        ON i.user_id = iu.id

    ORDER BY atr.created_at DESC
")->fetchAll();


include __DIR__ . '/../includes/header.php';


// ================================================================
// FORM VALUE HELPER
// ================================================================

function taskFieldValue(
    $clean,
    $key
) {

    return htmlspecialchars(
        (string)(
            $clean[$key]
            ?? ''
        )
    );
}

?>


<!-- ============================================================
     PAGE HEADER
============================================================= -->

<div class="page-toolbar">

    <div>

        <h1>
            Additional Task Requests
        </h1>

        <p>
            Enter task details, find available instructors,
            and assign one directly.
        </p>

    </div>

</div>


<!-- ============================================================
     SUCCESS MESSAGE
============================================================= -->

<?php if (isset($_SESSION['success'])): ?>

    <div class="alert alert-success">

        <?= htmlspecialchars(
            $_SESSION['success']
        ) ?>

        <?php
        unset(
            $_SESSION['success']
        );
        ?>

    </div>

<?php endif; ?>


<!-- ============================================================
     ERROR MESSAGE
============================================================= -->

<?php if (!empty($formErrors)): ?>

    <div class="alert alert-danger">

        <div>

            <strong>
                Please fix the following:
            </strong>

            <ul
                style="
                    margin:6px 0 0 18px;
                    padding:0;
                "
            >

                <?php foreach (
                    $formErrors
                    as $err
                ): ?>

                    <li>
                        <?= htmlspecialchars($err) ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    </div>

<?php endif; ?>


<!-- ============================================================
     PREFERRED INSTRUCTOR MESSAGE
============================================================= -->

<?php if (
    $preferredInstructorId > 0
    && !$searched
): ?>

    <div class="alert alert-info">

        Assigning for

        <strong>
            <?= htmlspecialchars(
                $preferredInstructorName
                ?: 'the selected instructor'
            ) ?>
        </strong>

        — enter the task date &amp; time below and search
        to confirm they're free for that slot.

    </div>

<?php endif; ?>


<!-- ============================================================
     TASK DETAILS / UPDATE FORM
============================================================= -->

<div class="card">

    <div
        class="
            card-header
            d-flex
            justify-content-between
            align-items-center
        "
    >

        <h5 class="mb-0">

            <?php if ($editTask): ?>

                Update Task Request

            <?php else: ?>

                Task Details

            <?php endif; ?>

        </h5>


        <?php if ($editTask): ?>

            <a
                href="additional_tasks.php"
                class="btn btn-sm btn-outline-secondary"
            >
                Cancel
            </a>

        <?php endif; ?>

    </div>


    <div class="card-body">


        <?php if ($editTask): ?>

            <!-- UPDATE FORM -->

            <form
                method="POST"
                class="row g-3"
            >

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action"
                    value="update_task_request"
                >

                <input
                    type="hidden"
                    name="request_id"
                    value="<?= (int)$editTask['id'] ?>"
                >


        <?php else: ?>

            <!-- SEARCH FORM -->

            <form
                method="GET"
                class="row g-3"
            >

                <?php if (
                    $preferredInstructorId > 0
                ): ?>

                    <input
                        type="hidden"
                        name="preferred_instructor_id"
                        value="<?= (int)$preferredInstructorId ?>"
                    >

                <?php endif; ?>

        <?php endif; ?>


        <!-- ====================================================
             TASK TITLE
        ===================================================== -->

        <div class="col-md-4">

            <label class="form-label">
                Task Title
            </label>

            <input
                name="title"
                class="form-control"
                placeholder="Task title / lecturer request"
                maxlength="150"
                value="<?= taskFieldValue(
                    $clean,
                    'title'
                ) ?>"
                required
            >

        </div>


        <!-- ====================================================
             TASK TYPE
        ===================================================== -->

        <div class="col-md-2">

            <label class="form-label">
                Task Type
            </label>

            <select
                name="task_type_id"
                class="form-select"
                required
            >

                <option value="">
                    Select type
                </option>


                <?php foreach (
                    $taskTypes
                    as $t
                ): ?>

                    <option
                        value="<?= (int)$t['id'] ?>"
                        <?= (
                            isset(
                                $clean['task_type_id']
                            )
                            &&
                            (int)$clean['task_type_id']
                            ===
                            (int)$t['id']
                        )
                        ? 'selected'
                        : ''
                        ?>
                    >
                        <?= htmlspecialchars(
                            $t['name']
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- ====================================================
             PREFERRED DATE
        ===================================================== -->

        <div class="col-md-2">

            <label class="form-label">
                Preferred Date
            </label>

            <input
                type="date"
                name="preferred_date"
                class="form-control"
                value="<?= taskFieldValue(
                    $clean,
                    'preferred_date'
                ) ?>"
                required
            >

        </div>

<!-- ====================================================
             START AND END TIME
        ===================================================== -->
<div class="col-md-2"><label>Start Time</label><input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars(substr($editTimetable['start_time'],0,5)) ?>" required></div> 
<div class="col-md-2"><label>End Time</label><input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars(substr($editTimetable['end_time'],0,5)) ?>" required></div>


        <!-- ====================================================
             URGENCY
        ===================================================== -->

        <div class="col-md-2">

            <label class="form-label">
                Urgency
            </label>

            <select
                name="urgency"
                class="form-select"
            >

                <?php foreach (
                    $allowedUrgency
                    as $u
                ): ?>

                    <option
                        <?= (
                            isset(
                                $clean['urgency']
                            )
                            &&
                            $clean['urgency']
                            === $u
                        )
                        ? 'selected'
                        : ''
                        ?>
                    >
                        <?= htmlspecialchars($u) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- ====================================================
             LOCATION
        ===================================================== -->

  <div class="col-md-2">
    <label class="form-label">Location</label>

    <select name="location" id="location" class="form-select">
        <option value="">Select Lecture Room</option>

        <?php
        $lectureRooms = $pdo->query("
            SELECT id, room_name
            FROM lecture_rooms
            WHERE status = 'Available'
            ORDER BY room_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lectureRooms as $room):
        ?>
            <option value="<?= htmlspecialchars($room['room_name']) ?>"
                <?= taskFieldValue($clean, 'location') === $room['room_name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($room['room_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


        <!-- ====================================================
             HOURS
        ===================================================== -->

        <div class="col-md-1">

            <label class="form-label">
                Hours
            </label>

            <input
                type="number"
                step="0.25"
                min="0.25"
                max="24"
                name="duration_hours"
                class="form-control"
                value="<?= htmlspecialchars(
                    (string)(
                        $clean['duration_hours']
                        ?? $clean['duration']
                        ?? '2'
                    )
                ) ?>"
            >

        </div>


        <!-- ====================================================
             PREFERRED STREAM
        ===================================================== -->

        <div class="col-md-2">

            <label class="form-label">
                Preferred Stream
            </label>

            <select
                name="stream_id"
                class="form-select"
            >

                <option value="">
                    Any Stream
                </option>


                <?php foreach (
                    $streams
                    as $s
                ): ?>

                    <option
                        value="<?= (int)$s['id'] ?>"
                        <?= (
                            isset(
                                $clean['stream_id']
                            )
                            &&
                            (int)$clean['stream_id']
                            ===
                            (int)$s['id']
                        )
                        ? 'selected'
                        : ''
                        ?>
                    >
                        <?= htmlspecialchars(
                            $s['name']
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- ====================================================
             DESCRIPTION
        ===================================================== -->

        <div class="col-md-6">

            <label class="form-label">
                Description
            </label>

            <input
                name="description"
                class="form-control"
                placeholder="Description"
                maxlength="500"
                value="<?= taskFieldValue(
                    $clean,
                    'description'
                ) ?>"
            >

        </div>


        <!-- ====================================================
             FORM ACTION
        ===================================================== -->

        <div class="col-12">

            <?php if ($editTask): ?>

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    Update Task Request

                </button>


                <a
                    href="additional_tasks.php"
                    class="btn btn-outline-secondary ms-2"
                >
                    Cancel
                </a>


            <?php else: ?>

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    Find Available Instructors

                </button>

            <?php endif; ?>

        </div>


        </form>

    </div>

</div>


<!-- ============================================================
     AVAILABLE INSTRUCTORS
============================================================= -->

<?php if (
    $searched
    && empty($formErrors)
): ?>

    <div class="card">

        <div class="card-header">

            <h5>
                Available Instructors
            </h5>

            <span class="text-muted small">
                Sorted by lowest workload
            </span>

        </div>


        <div class="card-body">


            <?php if (
                empty($suggestions)
            ): ?>

                <div class="alert alert-warning">

                    No available instructors found
                    for this date/time.

                    Adjust the details and
                    search again.

                </div>


            <?php else: ?>


                <div class="table-responsive">

                    <table
                        class="
                            table
                            table-hover
                            align-middle
                        "
                    >

                        <thead>

                            <tr>

                                <th>
                                    Instructor
                                </th>

                                <th>
                                    Employee ID
                                </th>

                                <th>
                                    Stream
                                </th>

                                <th>
                                    Current Workload (hrs)
                                </th>

                                <th class="text-end">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $suggestions
                                as $sug
                            ): ?>


                                <?php

                                $isPreferred =
                                    $preferredInstructorId > 0
                                    &&
                                    (
                                        (int)
                                        $sug[
                                            'instructor_id'
                                        ]
                                        ===
                                        $preferredInstructorId
                                    );

                                ?>


                                <tr
                                    <?=
                                        $isPreferred
                                        ? ' style="background:var(--soft);"'
                                        : ''
                                    ?>
                                >


                                    <td data-label="Instructor">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $sug['name']
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            $isPreferred
                                        ): ?>

                                            <span
                                                class="badge bg-primary"
                                                style="
                                                    margin-left:6px;
                                                "
                                            >
                                                Requested
                                            </span>

                                        <?php endif; ?>


                                        <br>

                                        <span
                                            class="
                                                small
                                                text-muted
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $sug['designation']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td data-label="Employee ID">

                                        <?= htmlspecialchars(
                                            $sug['employee_id']
                                        ) ?>

                                    </td>


                                    <td data-label="Stream">

                                        <?= htmlspecialchars(
                                            $sug['stream']
                                        ) ?>

                                    </td>


                                    <td data-label="Workload">

                                        <span
                                            class="
                                                badge
                                                <?=
                                                    $sug[
                                                        'current_workload'
                                                    ] > 30

                                                    ? 'bg-danger'

                                                    : (
                                                        $sug[
                                                            'current_workload'
                                                        ] > 15

                                                        ? 'bg-warning'

                                                        : 'bg-success'
                                                    )
                                                ?>
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                (string)
                                                $sug[
                                                    'current_workload'
                                                ]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td
                                        data-label="Action"
                                        class="text-end"
                                    >

                                        <form
                                            method="post"
                                            style="
                                                display:inline-block;
                                            "
                                        >

                                            <?= csrf_field() ?>


                                            <input
                                                type="hidden"
                                                name="title"
                                                value="<?= taskFieldValue(
                                                    $clean,
                                                    'title'
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="description"
                                                value="<?= taskFieldValue(
                                                    $clean,
                                                    'description'
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="task_type_id"
                                                value="<?= (int)(
                                                    $clean[
                                                        'task_type_id'
                                                    ]
                                                    ?? 0
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="preferred_date"
                                                value="<?= taskFieldValue(
                                                    $clean,
                                                    'preferred_date'
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="start_time"
                                                value="<?= taskFieldValue(
                                                    $clean,
                                                    'start_time'
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="end_time"
                                                value="<?= taskFieldValue(
                                                    $clean,
                                                    'end_time'
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="location"
                                                value="<?= taskFieldValue(
                                                    $clean,
                                                    'location'
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="urgency"
                                                value="<?= taskFieldValue(
                                                    $clean,
                                                    'urgency'
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="duration_hours"
                                                value="<?= taskFieldValue(
                                                    $clean,
                                                    'duration'
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="stream_id"
                                                value="<?= (int)(
                                                    $clean[
                                                        'stream_id'
                                                    ]
                                                    ?? 0
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="assign_instructor_id"
                                                value="<?= (int)(
                                                    $sug[
                                                        'instructor_id'
                                                    ]
                                                ) ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-success
                                                "
                                                onclick="return confirm('Assign this task to <?= htmlspecialchars(
                                                    addslashes(
                                                        $sug['name']
                                                    )
                                                ) ?>?')"
                                            >

                                                Select &amp; Assign

                                            </button>

                                        </form>

                                    </td>

                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


                <div
                    class="alert alert-info"
                    style="margin-top:14px;"
                >

                    Selecting an instructor immediately
                    creates the task request, assigns it
                    to them, and sends them a notification
                    — no further steps needed.

                </div>


            <?php endif; ?>


        </div>

    </div>

<?php endif; ?>


<!-- ============================================================
     TASK REQUESTS
============================================================= -->

<div class="card">

    <div class="card-header">

        <h5>
            Task Requests
        </h5>

        <span class="text-muted small">
            <?= count($tasks) ?> requests
        </span>

    </div>


    <div class="card-body">


        <div class="table-responsive">

            <table
                class="
                    table
                    table-hover
                    align-middle
                "
            >

                <thead>

                    <tr>

                        <th>
                            Title
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Requested By
                        </th>

                        <th>
                            Assigned To
                        </th>

                        <th>
                            Date/Time
                        </th>

                        <th>
                            Urgency
                        </th>

                        <th>
                            Status
                        </th>

                        <th class="text-end">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        empty($tasks)
                    ): ?>

                        <tr>

                            <td
                                colspan="8"
                                class="
                                    text-muted
                                    text-center
                                    py-4
                                "
                            >
                                No additional task requests yet.
                            </td>

                        </tr>

                    <?php endif; ?>


                    <?php foreach (
                        $tasks
                        as $task
                    ): ?>

                        <tr>


                            <!-- TITLE -->

                            <td data-label="Title">

                                <strong>
                                    <?= htmlspecialchars(
                                        $task['title']
                                    ) ?>
                                </strong>

                            </td>


                            <!-- TYPE -->

                            <td data-label="Type">

                                <?= htmlspecialchars(
                                    $task['task_type']
                                ) ?>

                            </td>


                            <!-- REQUESTED BY -->

                            <td data-label="Requested By">

                                <?= htmlspecialchars(
                                    $task['requested_by_name']
                                ) ?>

                            </td>


                            <!-- ASSIGNED TO -->

                            <td data-label="Assigned To">

                                <?php if (
                                    $task[
                                        'assigned_instructor_name'
                                    ]
                                ): ?>

                                    <?= htmlspecialchars(
                                        $task[
                                            'assigned_instructor_name'
                                        ]
                                    ) ?>

                                <?php else: ?>

                                    <span
                                        class="
                                            text-muted
                                        "
                                    >
                                        — Unassigned —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- DATE / TIME -->

                            <td data-label="Date/Time">

                                <?= formatDate(
                                    $task[
                                        'preferred_date'
                                    ]
                                ) ?>

                                <br>

                                <span
                                    class="
                                        small
                                        text-muted
                                    "
                                >

                                    <?= formatTime(
                                        $task[
                                            'start_time'
                                        ]
                                    ) ?>

                                    -

                                    <?= formatTime(
                                        $task[
                                            'end_time'
                                        ]
                                    ) ?>

                                </span>

                            </td>


                            <!-- URGENCY -->

                            <td data-label="Urgency">

                                <?= getStatusBadge(
                                    $task['urgency']
                                ) ?>

                            </td>


                            <!-- STATUS -->

                            <td data-label="Status">

                                <?= getStatusBadge(
                                    $task['status']
                                ) ?>

                            </td>


                            <!-- ACTIONS -->

                            <td
                                data-label="Actions"
                                class="text-end"
                                style="
                                    white-space:nowrap;
                                "
                            >


                                <!-- UPDATE BUTTON -->

                                <a
                                    href="
                                        additional_tasks.php?edit_id=<?= (int)(
                                            $task['id']
                                        )
                                        ?>
                                    "
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-primary
                                    "
                                    title="
                                        Update this task request
                                    "
                                >

                                    Update

                                </a>


                                <!-- DELETE BUTTON -->

                                <form
                                    method="POST"
                                    style="
                                        display:inline-block;
                                        margin-left:6px;
                                    "
                                >

                                    <?= csrf_field() ?>


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete_task_request"
                                    >


                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?= (int)(
                                            $task['id']
                                        ) ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="
                                            btn
                                            btn-sm
                                            btn-outline-danger
                                        "
                                        title="
                                            Delete this task request
                                        "
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this request? This will also remove its instructor assignment.'
                                            );
                                        "
                                    >
                                        Delete

                                    </button>

                                </form>


                            </td>


                        </tr>

                    <?php endforeach; ?>


                </tbody>

            </table>

        </div>

    </div>

</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>