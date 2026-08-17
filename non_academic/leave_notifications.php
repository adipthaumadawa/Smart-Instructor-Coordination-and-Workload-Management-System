<?php
/**
 * ============================================================
 * LEAVE NOTIFICATIONS
 * Smart Instructor Coordination and Workload Management System
 * Non-Academic Staff
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Leave Notifications";


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

include __DIR__ . '/../includes/header.php';


/*
|--------------------------------------------------------------------------
| GET LEAVE NOTIFICATIONS
|--------------------------------------------------------------------------
|
| Notifications are created when an instructor records leave.
| Non-Academic Staff only view the notification.
|
*/

$notifications = [];
$error = '';

try {

    /*
    |--------------------------------------------------------------------------
    | Get leave records
    |--------------------------------------------------------------------------
    |
   
    |
    */

    $stmt = $pdo->query("
        SELECT
            lr.id,
            lr.instructor_id,
            lr.leave_type,
            lr.start_date,
            lr.end_date,
            lr.reason,
            lr.status,
            lr.created_at,
            u.full_name
        FROM leave_records lr
        INNER JOIN users u
            ON lr.instructor_id = u.id
        ORDER BY
            lr.created_at DESC
        LIMIT 50
    ");

    $notifications =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    $error =
        'Unable to load leave notifications.';
}

?>


<style>

/* ============================================================
   LEAVE NOTIFICATIONS PAGE

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

    margin-bottom: 15px;
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

    background: #1f2937;

    margin-bottom: 25px;
}


/* ============================================================
   CARD
============================================================ */

.leave-page .card {

    background: #ffffff;

    border: 1px solid #1f2937;

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

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 18px;
}

.leave-page .section-icon {

    width: 42px;

    height: 42px;

    border-radius: 10px;

    background: #eef2ff;

    color: #3730a3;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;

    flex-shrink: 0;
}

.leave-page .section-heading h2 {

    margin: 0;

    font-size: 20px;

    font-weight: 700;

    color: #111827;
}

.leave-page .section-heading p {

    margin: 5px 0 0;

    color: #6b7280;

    font-size: 14px;
}


/* ============================================================
   CARD DIVIDER
============================================================ */

.leave-page .card-divider {

    height: 1px;

    background: #d1d5db;

    margin-bottom: 22px;
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
   NOTIFICATION ITEM
============================================================ */

.leave-page .notification-item {

    border: 1px solid #d1d5db;

    border-radius: 12px;

    padding: 18px;

    margin-bottom: 14px;

    background: #ffffff;

    transition: 0.2s ease;
}

.leave-page .notification-item:hover {

    border-color: #2563eb;

    box-shadow:
        0 3px 10px
        rgba(0, 0, 0, 0.05);
}


/* ============================================================
   NOTIFICATION HEADER
============================================================ */

.leave-page .notification-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 15px;

    margin-bottom: 12px;
}

.leave-page .notification-title {

    display: flex;

    align-items: center;

    gap: 10px;
}

.leave-page .notification-icon {

    width: 36px;

    height: 36px;

    border-radius: 9px;

    background: #eef2ff;

    color: #3730a3;

    display: flex;

    align-items: center;

    justify-content: center;
}

.leave-page .notification-title h3 {

    margin: 0;

    font-size: 16px;

    font-weight: 700;

    color: #111827;
}

.leave-page .notification-date {

    color: #6b7280;

    font-size: 12px;

    white-space: nowrap;
}


/* ============================================================
   NOTIFICATION DETAILS
============================================================ */

.leave-page .details-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(180px, 1fr)
        );

    gap: 12px;

    margin-top: 12px;
}

.leave-page .detail-box {

    background: #f9fafb;

    border: 1px solid #e5e7eb;

    border-radius: 9px;

    padding: 11px 13px;
}

.leave-page .detail-label {

    display: block;

    font-size: 12px;

    font-weight: 600;

    color: #6b7280;

    margin-bottom: 4px;
}

.leave-page .detail-value {

    font-size: 14px;

    font-weight: 600;

    color: #111827;
}


/* ============================================================
   REASON
============================================================ */

.leave-page .reason-box {

    margin-top: 14px;

    padding: 13px 15px;

    background: #f9fafb;

    border: 1px solid #e5e7eb;

    border-radius: 9px;
}

.leave-page .reason-label {

    font-size: 12px;

    font-weight: 700;

    color: #6b7280;

    margin-bottom: 5px;
}

.leave-page .reason-text {

    font-size: 14px;

    color: #374151;

    line-height: 1.5;
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

    font-weight: 700;
}

.leave-page .status-pending {

    background: #fef3c7;

    color: #92400e;
}

.leave-page .status-approved {

    background: #dcfce7;

    color: #166534;
}

.leave-page .status-rejected {

    background: #fee2e2;

    color: #991b1b;
}

.leave-page .status-default {

    background: #e5e7eb;

    color: #374151;
}


/* ============================================================
   EMPTY STATE
============================================================ */

.leave-page .empty-state {

    border: 1px dashed #9ca3af;

    border-radius: 12px;

    padding: 35px;

    text-align: center;

    background: #ffffff;
}

.leave-page .empty-icon {

    font-size: 34px;

    color: #6b7280;

    margin-bottom: 10px;
}

.leave-page .empty-state h3 {

    margin: 0;

    font-size: 18px;

    font-weight: 700;

    color: #111827;
}

.leave-page .empty-state p {

    margin: 7px 0 0;

    color: #6b7280;

    font-size: 14px;
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

    .leave-page .notification-header {

        flex-direction: column;
    }

    .leave-page .notification-date {

        white-space: normal;
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
                Leave Notifications
            </h1>

            <p>
                View instructor leave notifications and related information.
            </p>

        </div>

    </div>


    <!-- ======================================================
         HORIZONTAL DIVIDER
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
         NOTIFICATIONS CARD
    ======================================================= -->

    <div class="card">

        <div class="card-body">


            <!-- CARD HEADER -->

            <div class="section-heading">

                <div class="section-icon">

                    <i class="fas fa-bell"></i>

                </div>

                <div>

                    <h2>
                        Instructor Leave Notifications
                    </h2>

                    <p>
                        Recent leave records submitted by instructors.
                    </p>

                </div>

            </div>


            <!-- CARD DIVIDER -->

            <div class="card-divider"></div>


            <!-- ==================================================
                 NOTIFICATION LIST
            =================================================== -->

            <?php if (empty($notifications)): ?>

                <div class="empty-state">

                    <div class="empty-icon">

                        <i class="fas fa-bell-slash"></i>

                    </div>

                    <h3>
                        No Leave Notifications
                    </h3>

                    <p>
                        There are currently no instructor leave notifications.
                    </p>

                </div>

            <?php else: ?>


                <?php foreach ($notifications as $notification): ?>


                    <div class="notification-item">


                        <!-- ==================================================
                             NOTIFICATION HEADER
                        =================================================== -->

                        <div class="notification-header">


                            <div class="notification-title">

                                <div class="notification-icon">

                                    <i class="fas fa-calendar-xmark"></i>

                                </div>

                                <div>

                                    <h3>

                                        <?= htmlspecialchars(
                                            $notification['full_name']
                                        ) ?>

                                        submitted a leave record

                                    </h3>

                                </div>

                            </div>


                            <div class="notification-date">

                                <?= htmlspecialchars(
                                    date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $notification['created_at']
                                        )
                                    )
                                ) ?>

                            </div>


                        </div>


                        <!-- ==================================================
                             DETAILS
                        =================================================== -->

                        <div class="details-grid">


                            <!-- INSTRUCTOR -->

                            <div class="detail-box">

                                <span class="detail-label">
                                    Instructor
                                </span>

                                <span class="detail-value">

                                    <?= htmlspecialchars(
                                        $notification['full_name']
                                    ) ?>

                                </span>

                            </div>


                            <!-- LEAVE TYPE -->

                            <div class="detail-box">

                                <span class="detail-label">
                                    Leave Type
                                </span>

                                <span class="detail-value">

                                    <?= htmlspecialchars(
                                        $notification['leave_type']
                                    ) ?>

                                </span>

                            </div>


                            <!-- START DATE -->

                            <div class="detail-box">

                                <span class="detail-label">
                                    Start Date
                                </span>

                                <span class="detail-value">

                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $notification['start_date']
                                            )
                                        )
                                    ) ?>

                                </span>

                            </div>


                            <!-- END DATE -->

                            <div class="detail-box">

                                <span class="detail-label">
                                    End Date
                                </span>

                                <span class="detail-value">

                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $notification['end_date']
                                            )
                                        )
                                    ) ?>

                                </span>

                            </div>


                            <!-- STATUS -->

                            <div class="detail-box">

                                <span class="detail-label">
                                    Status
                                </span>

                                <span class="detail-value">


                                    <?php
                                    $status =
                                        $notification['status'] ?? '';

                                    if (
                                        strtolower($status) ===
                                        'pending'
                                    ):

                                    ?>

                                        <span
                                            class="
                                                status-badge
                                                status-pending
                                            "
                                        >
                                            Pending
                                        </span>


                                    <?php
                                    elseif (
                                        strtolower($status) ===
                                        'approved'
                                    ):

                                    ?>

                                        <span
                                            class="
                                                status-badge
                                                status-approved
                                            "
                                        >
                                            Approved
                                        </span>


                                    <?php
                                    elseif (
                                        strtolower($status) ===
                                        'rejected'
                                    ):

                                    ?>

                                        <span
                                            class="
                                                status-badge
                                                status-rejected
                                            "
                                        >
                                            Rejected
                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="
                                                status-badge
                                                status-default
                                            "
                                        >
                                            <?= htmlspecialchars(
                                                $status !== ''
                                                    ? $status
                                                    : 'Recorded'
                                            ) ?>
                                        </span>

                                    <?php endif; ?>


                                </span>

                            </div>


                            <!-- RECORDED DATE -->

                            <div class="detail-box">

                                <span class="detail-label">
                                    Recorded On
                                </span>

                                <span class="detail-value">

                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $notification['created_at']
                                            )
                                        )
                                    ) ?>

                                </span>

                            </div>


                        </div>


                        <!-- ==================================================
                             REASON
                        =================================================== -->

                        <?php
                        $reason =
                            trim(
                                $notification['reason'] ?? ''
                            );
                        ?>

                        <?php if ($reason !== ''): ?>

                            <div class="reason-box">

                                <div class="reason-label">
                                    Leave Reason / Remarks
                                </div>

                                <div class="reason-text">

                                    <?= nl2br(
                                        htmlspecialchars($reason)
                                    ) ?>

                                </div>

                            </div>

                        <?php endif; ?>


                    </div>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>

    </div>


</div>


<?php

include __DIR__ . '/../includes/footer.php';

?>