<?php
/**
 * Smart Instructor Suggestion Module
 * This is one of the core features of the project.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Smart Instructor Suggestions";

$suggestions = [];
$searchError = '';

// Check if user submitted a search
$hasSearched = (isset($_GET['search_submitted']) && $_GET['search_submitted'] === '1');

// Get search criteria without filling default dates/times
$taskTypeId = (int)($_GET['task_type_id'] ?? 0);
$date = trim((string)($_GET['date'] ?? ''));
$startTime = trim((string)($_GET['start_time'] ?? ''));
$endTime = trim((string)($_GET['end_time'] ?? ''));
$streamId = (int)($_GET['stream_id'] ?? 0);

// Validate inputs only upon search execution
if ($hasSearched) {
    if (empty($date) || !DateTime::createFromFormat('Y-m-d', $date)) {
        $searchError = 'Please provide a valid date.';
    } elseif (empty($startTime) || !DateTime::createFromFormat('H:i', $startTime)) {
        $searchError = 'Please provide a valid start time.';
    } elseif (empty($endTime) || !DateTime::createFromFormat('H:i', $endTime)) {
        $searchError = 'Please provide a valid end time.';
    } elseif (strtotime($endTime) <= strtotime($startTime)) {
        $searchError = 'End time must be after start time.';
    }

    if ($searchError === '') {
        $suggestions = getSmartSuggestions($taskTypeId ?: null, $date, $startTime, $endTime, $streamId ?: null, 8);
    }
}

$taskTypes = $pdo->query("SELECT * FROM task_types ORDER BY name")->fetchAll();
$streams = $pdo->query("SELECT * FROM academic_streams ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Smart Instructor Suggestions</h1>
                    <p>Find the best available instructor for a task, ranked by current workload.</p>
                </div>
            </div>

            <?php if ($searchError): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($searchError) ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header"><h5>Find Best Available Instructors</h5></div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="search_submitted" value="1">
                        
                        <div class="col-md-3">
                            <label class="form-label">Task Type (Optional)</label>
                            <select name="task_type_id" class="form-select">
                                <option value="">Any Task Type</option>
                                <?php foreach ($taskTypes as $tt): ?>
                                    <option value="<?= (int)$tt['id'] ?>" <?= $taskTypeId === (int)$tt['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tt['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars($startTime) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($endTime) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Preferred Stream</label>
                            <select name="stream_id" class="form-select">
                                <option value="">Any Stream</option>
                                <?php foreach ($streams as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= $streamId === (int)$s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Find</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($suggestions)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5>Recommended Instructors</h5>
                        <span class="text-muted small">Sorted by lowest workload</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Instructor</th>
                                        <th>Employee ID</th>
                                        <th>Stream</th>
                                        <th>Current Workload (hrs)</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suggestions as $index => $sug): ?>
                                    <tr>
                                        <td data-label="#"><?= $index + 1 ?></td>
                                        <td data-label="Instructor"><strong><?= htmlspecialchars($sug['name']) ?></strong><br><span class="small text-muted"><?= htmlspecialchars($sug['designation']) ?></span></td>
                                        <td data-label="Employee ID"><?= htmlspecialchars($sug['employee_id']) ?></td>
                                        <td data-label="Stream"><?= htmlspecialchars($sug['stream']) ?></td>
                                        <td data-label="Workload">
                                            <span class="badge <?= $sug['current_workload'] > 30 ? 'bg-danger' : ($sug['current_workload'] > 15 ? 'bg-warning' : 'bg-success') ?>">
                                                <?= htmlspecialchars((string)$sug['current_workload']) ?>
                                            </span>
                                        </td>
                                        <td data-label="Action" class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a href="additional_tasks.php?instructor_id=<?= (int)$sug['instructor_id'] ?>&date=<?= urlencode($date) ?>&task_type_id=<?= $taskTypeId ?>&start_time=<?= urlencode($startTime) ?>&end_time=<?= urlencode($endTime) ?>" class="btn btn-sm btn-success">
                                                    Assign Task
                                                </a>
                                                <a href="urgency_replacements.php?new_instructor_id=<?= (int)$sug['instructor_id'] ?>" class="btn btn-sm btn-outline-danger">
                                                    Urgent Replacement
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <strong>How it works (for viva):</strong>&nbsp; The system checks: Active status &rarr; Not on leave &rarr; No timetable conflict &rarr; No task conflict &rarr; Sorts by lowest workload.
                </div>
            <?php elseif ($hasSearched && $searchError === ''): ?>
                <div class="alert alert-warning">No suitable instructors found for the selected criteria.</div>
            <?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>