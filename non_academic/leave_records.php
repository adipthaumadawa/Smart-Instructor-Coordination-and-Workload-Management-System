<?php
/**
 * ============================================================
 * LEAVE RECORDS
 * Smart Instructor Coordination and Workload Management System
 * Non-Academic Staff
 * ============================================================
 *
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Leave Records";


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$error = '';

$leaveRecords = [];

$search = trim(
    $_GET['search'] ?? ''
);

$statusFilter = trim(
    $_GET['status'] ?? ''
);

$leaveTypeFilter = trim(
    $_GET['leave_type'] ?? ''
);


/*
|--------------------------------------------------------------------------
| LOAD LEAVE RECORDS
|--------------------------------------------------------------------------
*/

try {

    $sql = "
        SELECT
            l.id,
            l.instructor_id,
            u.full_name AS instructor_name,
            l.leave_type,
            l.start_date,
            l.end_date,
            l.reason,
            l.status,
            l.approved_by,
            l.created_at,
            l.updated_at

        FROM leave_records l

        INNER JOIN users u
            ON l.instructor_id = u.id

        WHERE 1 = 1
    ";


    $params = [];


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $sql .= "
            AND (
                u.full_name LIKE ?
                OR l.leave_type LIKE ?
                OR l.reason LIKE ?
            )
        ";

        $searchValue =
            '%' . $search . '%';

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

    if ($statusFilter !== '') {

        $sql .= "
            AND l.status = ?
        ";

        $params[] =
            $statusFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | LEAVE TYPE FILTER
    |--------------------------------------------------------------------------
    */

    if ($leaveTypeFilter !== '') {

        $sql .= "
            AND l.leave_type = ?
        ";

        $params[] =
            $leaveTypeFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    */

    $sql .= "
        ORDER BY
            l.start_date DESC,
            l.created_at DESC
    ";


    /*
    |--------------------------------------------------------------------------
    | EXECUTE
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare($sql);

    $stmt->execute($params);


    $leaveRecords =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    $error =
        'Unable to load leave records.';
}


/*
|--------------------------------------------------------------------------
| LOAD LEAVE TYPES
|--------------------------------------------------------------------------
*/

$leaveTypes = [];


try {

    $stmt = $pdo->query("
        SELECT DISTINCT
            leave_type

        FROM leave_records

        WHERE leave_type IS NOT NULL
          AND leave_type <> ''

        ORDER BY
            leave_type ASC
    ");


    $leaveTypes =
        $stmt->fetchAll(
            PDO::FETCH_COLUMN
        );

} catch (Throwable $e) {

    $leaveTypes = [];
}


/*
|--------------------------------------------------------------------------
| LOAD STATUSES
|--------------------------------------------------------------------------
*/

$statuses = [];


try {

    $stmt = $pdo->query("
        SELECT DISTINCT
            status

        FROM leave_records

        WHERE status IS NOT NULL
          AND status <> ''

        ORDER BY
            status ASC
    ");


    $statuses =
        $stmt->fetchAll(
            PDO::FETCH_COLUMN
        );

} catch (Throwable $e) {

    $statuses = [];
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

include __DIR__ . '/../includes/header.php';

?>


<style>

/* ============================================================
   LEAVE RECORDS PAGE
   
============================================================ */

.leave-page {

    width: 100%;
}

.leave-page * {

    box-sizing: border-box;
}


/* ============================================================
   PAGE HEADER
============================================================ */

.leave-page .page-toolbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 0;
}

.leave-page .page-toolbar h1 {

    margin: 0;

    font-size: 28px;

    font-weight: 700;

    color: #111827;
}

.leave-page .page-toolbar p {

    margin: 7px 0 0;

    color: #6b7280;

    font-size: 14px;
}


/* ============================================================
   HORIZONTAL DIVIDER
============================================================ */

.leave-page .page-divider {

    height: 1px;

    background: #e5e7eb;

    margin-top: 20px;

    margin-bottom: 25px;
}


/* ============================================================
   CARD
============================================================ */

.leave-page .card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    margin-bottom: 24px;

    overflow: hidden;
}

.leave-page .card-body {

    padding: 25px;
}


/* ============================================================
   SECTION HEADING
============================================================ */

.leave-page .section-heading {

    margin-bottom: 22px;
}

.leave-page .section-heading h2 {

    margin: 0;

    font-size: 20px;

    font-weight: 700;

    color: #111827;
}

.leave-page .section-heading p {

    margin: 6px 0 0;

    color: #6b7280;

    font-size: 14px;
}


/* ============================================================
   FORM GRID
============================================================ */

.leave-page .form-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(180px, 1fr)
        );

    gap: 18px;
}


/* ============================================================
   FORM GROUP
============================================================ */

.leave-page .form-group {

    min-width: 0;
}


/* ============================================================
   LABEL
============================================================ */

.leave-page .form-group label {

    display: block;

    margin-bottom: 7px;

    font-size: 14px;

    font-weight: 600;

    color: #111827;
}


/* ============================================================
   FORM CONTROL
============================================================ */

.leave-page .form-control {

    width: 100%;

    min-height: 44px;

    padding: 10px 12px;

    border: 1px solid #d1d5db;

    border-radius: 9px;

    background: #ffffff;

    color: #111827;

    font-size: 14px;

    outline: none;
}

.leave-page .form-control:focus {

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(
            37,
            99,
            235,
            0.10
        );
}


/* ============================================================
   BUTTON AREA
============================================================ */

.leave-page .button-area {

    margin-top: 20px;

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}


/* ============================================================
   BUTTON
============================================================ */

.leave-page .btn {

    border: none;

    border-radius: 8px;

    padding: 11px 22px;

    font-size: 14px;

    font-weight: 600;

    cursor: pointer;

    text-decoration: none;

    display: inline-block;
}


/* ============================================================
   PRIMARY BUTTON
============================================================ */

.leave-page .btn-primary {

    background: #2563eb;

    color: #ffffff;
}

.leave-page .btn-primary:hover {

    background: #1d4ed8;
}


/* ============================================================
   OUTLINE BUTTON
============================================================ */

.leave-page .btn-outline {

    background: #ffffff;

    border: 1px solid #d1d5db;

    color: #374151;
}

.leave-page .btn-outline:hover {

    background: #f9fafb;
}


/* ============================================================
   ALERT
============================================================ */

.leave-page .alert {

    border-radius: 9px;

    padding: 13px 16px;

    margin-bottom: 20px;

    font-size: 14px;
}

.leave-page .alert-danger {

    background: #fef2f2;

    border: 1px solid #fecaca;

    color: #b91c1c;
}


/* ============================================================
   TABLE WRAPPER
============================================================ */

.leave-page .table-wrapper {

    overflow-x: auto;

    margin-top: 24px;
}


/* ============================================================
   TABLE
============================================================ */

.leave-page table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1050px;
}

.leave-page th {

    text-align: left;

    background: #f9fafb;

    border-bottom: 1px solid #e5e7eb;

    padding: 13px 14px;

    font-size: 13px;

    color: #374151;

    white-space: nowrap;
}

.leave-page td {

    padding: 14px;

    border-bottom: 1px solid #f0f0f0;

    font-size: 14px;

    color: #374151;

    vertical-align: top;
}

.leave-page tr:last-child td {

    border-bottom: none;
}


/* ============================================================
   STATUS BADGES
============================================================ */

.leave-page .status-badge {

    display: inline-flex;

    align-items: center;

    padding: 5px 10px;

    border-radius: 999px;

    font-size: 12px;

    font-weight: 600;

    white-space: nowrap;
}


/* APPROVED */

.leave-page .status-approved {

    background: #dcfce7;

    color: #166534;
}


/* PENDING */

.leave-page .status-pending {

    background: #fef3c7;

    color: #92400e;
}


/* REJECTED */

.leave-page .status-rejected {

    background: #fee2e2;

    color: #991b1b;
}


/* RECORDED */

.leave-page .status-recorded {

    background: #dbeafe;

    color: #1e40af;
}


/* CANCELLED */

.leave-page .status-cancelled {

    background: #e5e7eb;

    color: #374151;
}


/* DEFAULT */

.leave-page .status-default {

    background: #f3f4f6;

    color: #374151;
}


/* ============================================================
   EMPTY STATE
============================================================ */

.leave-page .empty-state {

    margin-top: 20px;

    border: 1px dashed #d1d5db;

    border-radius: 10px;

    padding: 30px;

    text-align: center;
}

.leave-page .empty-state h3 {

    margin: 0;

    font-size: 17px;

    color: #111827;
}

.leave-page .empty-state p {

    color: #6b7280;

    margin-bottom: 0;
}


/* ============================================================
   MUTED TEXT
============================================================ */

.leave-page .muted {

    color: #9ca3af;

    font-size: 13px;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 700px) {

    .leave-page .card-body {

        padding: 18px;
    }

    .leave-page .page-toolbar h1 {

        font-size: 23px;
    }

}

</style>


<div class="leave-page">


    <!-- ======================================================
         PAGE HEADER
    ======================================================= -->

    <div class="page-toolbar">

        <div>

            <h1>
                Leave Records
            </h1>

            <p>
                View instructor leave records and availability information.
            </p>

        </div>

    </div>


    <!-- ======================================================
         DIVIDER
    ======================================================= -->

    <div class="page-divider"></div>


    <!-- ======================================================
         ERROR
    ======================================================= -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- ======================================================
         SEARCH / FILTER CARD
    ======================================================= -->

    <div class="card">

        <div class="card-body">


            <div class="section-heading">

                <h2>
                    Search Leave Records
                </h2>

                <p>
                    Search and filter recorded instructor leave information.
                </p>

            </div>


            <form method="GET">


                <div class="form-grid">


                    <!-- ==================================================
                         SEARCH
                    =================================================== -->

                    <div class="form-group">

                        <label>
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Instructor, leave type or reason"
                            value="<?= htmlspecialchars($search) ?>"
                        >

                    </div>


                    <!-- ==================================================
                         LEAVE TYPE
                    =================================================== -->

                    <div class="form-group">

                        <label>
                            Leave Type
                        </label>

                        <select
                            name="leave_type"
                            class="form-control"
                        >

                            <option value="">
                                All Leave Types
                            </option>


                            <?php foreach (
                                $leaveTypes
                                as $type
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars($type) ?>"
                                    <?= $leaveTypeFilter === $type
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars($type) ?>

                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>


                    <!-- ==================================================
                         STATUS
                    =================================================== -->

                    <div class="form-group">

                        <label>
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-control"
                        >

                            <option value="">
                                All Statuses
                            </option>


                            <?php foreach (
                                $statuses
                                as $status
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars($status) ?>"
                                    <?= $statusFilter === $status
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars($status) ?>

                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>


                </div>


                <div class="button-area">


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Search
                    </button>


                    <a
                        href="leave_records.php"
                        class="btn btn-outline"
                    >
                        Clear
                    </a>


                </div>


            </form>


        </div>

    </div>


    <!-- ======================================================
         LEAVE RECORDS CARD
    ======================================================= -->

    <div class="card">

        <div class="card-body">


            <div class="section-heading">

                <h2>
                    Leave Records
                </h2>

                <p>
                    View all recorded instructor leave information.
                </p>

            </div>


            <?php if (empty($leaveRecords)): ?>


                <!-- ==================================================
                     EMPTY
                =================================================== -->

                <div class="empty-state">

                    <h3>
                        No Leave Records
                    </h3>

                    <p>
                        No instructor leave records were found.
                    </p>

                </div>


            <?php else: ?>


                <!-- ==================================================
                     TABLE
                =================================================== -->

                <div class="table-wrapper">


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Instructor
                                </th>

                                <th>
                                    Leave Type
                                </th>

                                <th>
                                    Start Date
                                </th>

                                <th>
                                    End Date
                                </th>

                                <th>
                                    Reason
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Approved By
                                </th>

                                <th>
                                    Recorded Date
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $leaveRecords
                            as $leave
                        ): ?>


                            <tr>


                                <!-- ==================================================
                                     INSTRUCTOR
                                =================================================== -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $leave['instructor_name']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- ==================================================
                                     LEAVE TYPE
                                =================================================== -->

                                <td>

                                    <?= htmlspecialchars(
                                        $leave['leave_type']
                                    ) ?>

                                </td>


                                <!-- ==================================================
                                     START DATE
                                =================================================== -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $leave['start_date']
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            date(
                                                'd M Y',
                                                strtotime(
                                                    $leave['start_date']
                                                )
                                            )
                                        );

                                    } else {

                                        echo '<span class="muted">
                                                Not available
                                              </span>';
                                    }

                                    ?>

                                </td>


                                <!-- ==================================================
                                     END DATE
                                =================================================== -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $leave['end_date']
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            date(
                                                'd M Y',
                                                strtotime(
                                                    $leave['end_date']
                                                )
                                            )
                                        );

                                    } else {

                                        echo '<span class="muted">
                                                Not available
                                              </span>';
                                    }

                                    ?>

                                </td>


                                <!-- ==================================================
                                     REASON
                                =================================================== -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            trim(
                                                $leave['reason'] ?? ''
                                            )
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            $leave['reason']
                                        );

                                    } else {

                                        echo '<span class="muted">
                                                No reason provided
                                              </span>';
                                    }

                                    ?>

                                </td>


                                <!-- ==================================================
                                     STATUS
                                =================================================== -->

                                <td>

                                    <?php

                                    $status =
                                        trim(
                                            $leave['status'] ?? ''
                                        );


                                    $statusClass =
                                        'status-default';


                                    switch (
                                        strtolower($status)
                                    ) {

                                        case 'approved':

                                            $statusClass =
                                                'status-approved';

                                            break;


                                        case 'pending':

                                            $statusClass =
                                                'status-pending';

                                            break;


                                        case 'rejected':

                                            $statusClass =
                                                'status-rejected';

                                            break;


                                        case 'recorded':

                                            $statusClass =
                                                'status-recorded';

                                            break;


                                        case 'cancelled':

                                            $statusClass =
                                                'status-cancelled';

                                            break;
                                    }

                                    ?>


                                    <span
                                        class="
                                            status-badge
                                            <?= $statusClass ?>
                                        "
                                    >

                                        <?= htmlspecialchars(
                                            $status !== ''
                                                ? $status
                                                : 'Recorded'
                                        ) ?>

                                    </span>


                                </td>


                                <!-- ==================================================
                                     APPROVED BY
                                =================================================== -->

                                <td>

                                    <?php

                                   

                                    if (
                                        !empty(
                                            $leave['approved_by']
                                        )
                                    ) {

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Find approver
                                        |--------------------------------------------------------------------------
                                        */

                                        try {

                                            $approverStmt =
                                                $pdo->prepare("
                                                    SELECT full_name
                                                    FROM users
                                                    WHERE id = ?
                                                    LIMIT 1
                                                ");

                                            $approverStmt->execute([
                                                $leave['approved_by']
                                            ]);

                                            $approver =
                                                $approverStmt->fetch(
                                                    PDO::FETCH_ASSOC
                                                );


                                            if ($approver) {

                                                echo htmlspecialchars(
                                                    $approver['full_name']
                                                );

                                            } else {

                                                echo '<span class="muted">
                                                        Not available
                                                      </span>';
                                            }

                                        } catch (Throwable $e) {

                                            echo '<span class="muted">
                                                    Not available
                                                  </span>';
                                        }

                                    } else {

                                        echo '<span class="muted">
                                                Not approved
                                              </span>';
                                    }

                                    ?>

                                </td>


                                <!-- ==================================================
                                     CREATED DATE
                                =================================================== -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $leave['created_at']
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $leave['created_at']
                                                )
                                            )
                                        );

                                    } else {

                                        echo '<span class="muted">
                                                Not available
                                              </span>';
                                    }

                                    ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </div>

    </div>


</div>


<?php

include __DIR__ . '/../includes/footer.php';

?>