<?php
session_start();
require '../config/db.php';
require '../public/functions.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// ======================================
// FLASH MESSAGES (survive the redirect)
// ======================================
$message = $_SESSION['flash_message'] ?? '';
$error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_error']);

function flashSuccess($msg) {
    $_SESSION['flash_message'] = $msg;
}
function flashError($msg) {
    $_SESSION['flash_error'] = $msg;
}
function backToDashboard() {
    header("Location: dashboard.php");
    exit;
}

// ======================================
// APPROVE REQUEST
// ======================================
if (isset($_POST['approve_request'])) {
    $requestId = (int) $_POST['request_id'];

    $stmt = $pdo->prepare("
        UPDATE authorized_students
        SET status = 'approved', approved_at = NOW()
        WHERE id = :id AND status = 'pending'
    ");
    $stmt->execute([':id' => $requestId]);

    if ($stmt->rowCount() > 0) {
        flashSuccess("Student authorization request approved.");
    } else {
        flashError("This request was already processed.");
    }
    backToDashboard();
}

// ======================================
// DENY REQUEST
// ======================================
if (isset($_POST['deny_request'])) {
    $requestId = (int) $_POST['request_id'];

    $stmt = $pdo->prepare("
        UPDATE authorized_students
        SET status = 'denied', approved_at = NULL
        WHERE id = :id AND status = 'pending'
    ");
    $stmt->execute([':id' => $requestId]);

    if ($stmt->rowCount() > 0) {
        flashSuccess("Student authorization request denied.");
    } else {
        flashError("This request was already processed.");
    }
    backToDashboard();
}

// ======================================
// REVOKE AUTHORIZATION
// ======================================
if (isset($_POST['revoke_authorization'])) {
    $requestId = (int) $_POST['request_id'];

    $stmt = $pdo->prepare("
        UPDATE authorized_students
        SET status = 'denied', approved_at = NULL
        WHERE id = :id AND status = 'approved'
    ");
    $stmt->execute([':id' => $requestId]);

    if ($stmt->rowCount() > 0) {
        flashSuccess("Student authorization has been revoked.");
    } else {
        flashError("This authorization was already revoked.");
    }
    backToDashboard();
}

// ======================================
// CANCEL ROOM USAGE
// ======================================
if (isset($_POST['cancel_usage'])) {
    $usageId = (int) $_POST['usage_id'];

    $stmt = $pdo->prepare("DELETE FROM room_usage WHERE id = :id");
    $stmt->execute([':id' => $usageId]);

    flashSuccess("Room usage has been cancelled.");
    backToDashboard();
}

// ======================================
// UPDATE ROOM
// ======================================
if (isset($_POST['update_room'])) {
    $roomId   = (int) $_POST['room_id'];
    $roomName = trim($_POST['room_name'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $capacity = filter_var(
        $_POST['capacity'] ?? '',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 1000]]
    );

    if ($roomName === '' || $building === '') {
        flashError("Room name and building cannot be empty.");
    } elseif ($capacity === false) {
        flashError("Capacity must be a whole number from 1 to 1000.");
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM rooms
            WHERE room_name = :room_name
              AND building = :building
              AND id <> :id
            LIMIT 1
        ");
        $stmt->execute([
            ':room_name' => $roomName,
            ':building'  => $building,
            ':id'        => $roomId
        ]);

        if ($stmt->fetch()) {
            flashError("Another room in this building already uses that name.");
        } else {
            $stmt = $pdo->prepare("
                UPDATE rooms
                SET room_name = :room_name,
                    building  = :building,
                    capacity  = :capacity
                WHERE id = :id
            ");
            $stmt->execute([
                ':room_name' => $roomName,
                ':building'  => $building,
                ':capacity'  => $capacity,
                ':id'        => $roomId
            ]);
            flashSuccess("Room information has been updated.");
        }
    }
    backToDashboard();
}

// ======================================
// ADD TEACHER
// ======================================
if (isset($_POST['add_teacher'])) {
    $employeeId = trim($_POST['employee_id'] ?? '');
    $fullName   = trim($_POST['full_name'] ?? '');
    $department = trim($_POST['department'] ?? '');

    if ($employeeId === '' || $fullName === '' || $department === '') {
        flashError("Employee ID, full name and department are all required.");
    } else {
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE employee_id = :e LIMIT 1");
        $stmt->execute([':e' => $employeeId]);

        if ($stmt->fetch()) {
            flashError("A teacher with that Employee ID already exists.");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO teachers (employee_id, full_name, department)
                VALUES (:e, :f, :d)
            ");
            $stmt->execute([':e' => $employeeId, ':f' => $fullName, ':d' => $department]);
            flashSuccess("Teacher has been added.");
        }
    }
    backToDashboard();
}

// ======================================
// UPDATE TEACHER
// ======================================
if (isset($_POST['update_teacher'])) {
    $teacherId  = (int) $_POST['teacher_id'];
    $employeeId = trim($_POST['employee_id'] ?? '');
    $fullName   = trim($_POST['full_name'] ?? '');
    $department = trim($_POST['department'] ?? '');

    if ($employeeId === '' || $fullName === '' || $department === '') {
        flashError("Employee ID, full name and department are all required.");
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM teachers
            WHERE employee_id = :e AND id <> :id LIMIT 1
        ");
        $stmt->execute([':e' => $employeeId, ':id' => $teacherId]);

        if ($stmt->fetch()) {
            flashError("Another teacher already uses that Employee ID.");
        } else {
            $stmt = $pdo->prepare("
                UPDATE teachers
                SET employee_id = :e,
                    full_name   = :f,
                    department  = :d
                WHERE id = :id
            ");
            $stmt->execute([
                ':e'  => $employeeId,
                ':f'  => $fullName,
                ':d'  => $department,
                ':id' => $teacherId
            ]);
            flashSuccess("Teacher information has been updated.");
        }
    }
    backToDashboard();
}

// ======================================
// ADD RECURRING CLASS SCHEDULE
// ======================================
if (isset($_POST['add_schedule'])) {
    $teacherId  = (int) $_POST['teacher_id'];
    $roomId     = (int) $_POST['room_id'];
    $dayOfWeek  = (int) $_POST['day_of_week'];
    $startTime  = $_POST['start_time'];
    $endTime    = $_POST['end_time'];
    $validFrom  = $_POST['valid_from'];
    $validUntil = $_POST['valid_until'];
    $section    = trim($_POST['section'] ?? '');
    $purpose    = trim($_POST['purpose'] ?? '');

    if (!$teacherId || !$roomId) {
        flashError("Please pick a teacher and a room.");
        backToDashboard();
    } elseif ($purpose === '') {
        flashError("Purpose is required.");
        backToDashboard();
    } elseif (strtotime($endTime) <= strtotime($startTime)) {
        flashError("End time must be later than start time.");
        backToDashboard();
    } elseif (strtotime($validUntil) < strtotime($validFrom)) {
        flashError("End date cannot be before start date.");
        backToDashboard();
    }

    // --------------------------------------
    // DUPLICATE SCHEDULE PRE-CHECK
    // --------------------------------------
    $stmt = $pdo->prepare("
        SELECT id
        FROM teacher_schedules
        WHERE teacher_id  = :teacher_id
          AND room_id     = :room_id
          AND day_of_week = :day_of_week
          AND start_time  = :start_time
          AND end_time    = :end_time
          AND valid_from  = :valid_from
          AND valid_until = :valid_until
        LIMIT 1
    ");
    $stmt->execute([
        ':teacher_id'  => $teacherId,
        ':room_id'     => $roomId,
        ':day_of_week' => $dayOfWeek,
        ':start_time'  => $startTime,
        ':end_time'    => $endTime,
        ':valid_from'  => $validFrom,
        ':valid_until' => $validUntil
    ]);

    if ($stmt->fetch()) {
        flashError("This exact schedule already exists. Nothing was added.");
        backToDashboard();
    }

    // --------------------------------------
    // TRANSACTION: schedule row + expansion
    // --------------------------------------
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO teacher_schedules
                (teacher_id, room_id, day_of_week, start_time, end_time,
                 section, purpose, valid_from, valid_until)
            VALUES
                (:teacher_id, :room_id, :day_of_week, :start_time, :end_time,
                 :section, :purpose, :valid_from, :valid_until)
        ");
        $stmt->execute([
            ':teacher_id'  => $teacherId,
            ':room_id'     => $roomId,
            ':day_of_week' => $dayOfWeek,
            ':start_time'  => $startTime,
            ':end_time'    => $endTime,
            ':section'     => $section,
            ':purpose'     => $purpose,
            ':valid_from'  => $validFrom,
            ':valid_until' => $validUntil
        ]);
        $scheduleId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT full_name FROM teachers WHERE id = :id");
        $stmt->execute([':id' => $teacherId]);
        $teacherName = $stmt->fetchColumn();

        $inserted = 0;
        $skipped  = 0;
        $cursorTs = strtotime($validFrom);
        $endTs    = strtotime($validUntil);
        $nowTs    = time();

        $insertStmt = $pdo->prepare("
            INSERT INTO room_usage (
                schedule_id, room_id, user_type, teacher_id, student_id_number,
                user_name, section, purpose, start_time, end_time
            ) VALUES (
                :schedule_id, :room_id, 'teacher', :teacher_id, NULL,
                :user_name, :section, :purpose, :start_time, :end_time
            )
        ");

        while ($cursorTs <= $endTs) {
            if ((int) date('w', $cursorTs) === $dayOfWeek) {
                $dateStr = date('Y-m-d', $cursorTs);
                $sdt = $dateStr . ' ' . $startTime . ':00';
                $edt = $dateStr . ' ' . $endTime   . ':00';

                if (strtotime($sdt) <= $nowTs) {
                    $skipped++;
                } elseif (hasRoomOverlap($roomId, $sdt, $edt, $pdo)) {
                    $skipped++;
                } else {
                    $insertStmt->execute([
                        ':schedule_id' => $scheduleId,
                        ':room_id'     => $roomId,
                        ':teacher_id'  => $teacherId,
                        ':user_name'   => $teacherName,
                        ':section'     => $section,
                        ':purpose'     => $purpose,
                        ':start_time'  => $sdt,
                        ':end_time'    => $edt
                    ]);
                    $inserted++;
                }
            }
            $cursorTs += 86400;
        }

        if ($inserted === 0) {
            $pdo->rollBack();
            flashError("No sessions could be added — every date was in the past or conflicted with an existing booking.");
        } else {
            $pdo->commit();
            $msg = "Schedule saved. Added {$inserted} class session(s).";
            if ($skipped > 0) {
                $msg .= " Skipped {$skipped} date(s) due to conflicts or past dates.";
            }
            flashSuccess($msg);
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flashError("Could not save schedule: " . $e->getMessage());
    }

    backToDashboard();
}

// ======================================
// DELETE SCHEDULE
// ======================================
if (isset($_POST['delete_schedule'])) {
    $scheduleId = (int) $_POST['schedule_id'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            DELETE FROM room_usage
            WHERE schedule_id = :sid
              AND start_time > NOW()
        ");
        $stmt->execute([':sid' => $scheduleId]);

        $stmt = $pdo->prepare("DELETE FROM teacher_schedules WHERE id = :id");
        $stmt->execute([':id' => $scheduleId]);

        $pdo->commit();
        flashSuccess("Schedule removed. Future sessions cancelled; past sessions kept.");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flashError("Could not delete schedule: " . $e->getMessage());
    }

    backToDashboard();
}

// ======================================
// GET ROOMS
// ======================================
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_name ASC");
$rooms = $stmt->fetchAll();

// ======================================
// GET TEACHERS
// ======================================
$stmt = $pdo->query("SELECT * FROM teachers ORDER BY full_name ASC");
$teachers = $stmt->fetchAll();

// ======================================
// GET PENDING REQUESTS
// ======================================
$stmt = $pdo->query("
    SELECT authorized_students.*, rooms.room_name
    FROM authorized_students
    LEFT JOIN rooms ON authorized_students.room_id = rooms.id
    WHERE authorized_students.status = 'pending'
    ORDER BY authorized_students.requested_at ASC
");
$pendingRequests = $stmt->fetchAll();

// ======================================
// GET APPROVED STUDENTS
// ======================================
$stmt = $pdo->query("
    SELECT authorized_students.*, rooms.room_name
    FROM authorized_students
    LEFT JOIN rooms ON authorized_students.room_id = rooms.id
    WHERE authorized_students.status = 'approved'
      AND authorized_students.valid_until >= NOW()
    ORDER BY authorized_students.approved_at DESC
");
$approvedStudents = $stmt->fetchAll();

// ======================================
// GET DENIED REQUESTS
// ======================================
$stmt = $pdo->query("
    SELECT authorized_students.*, rooms.room_name
    FROM authorized_students
    LEFT JOIN rooms ON authorized_students.room_id = rooms.id
    WHERE authorized_students.status = 'denied'
    ORDER BY authorized_students.requested_at DESC
");
$deniedRequests = $stmt->fetchAll();

// ======================================
// GET ROOM USAGE
// ======================================
$stmt = $pdo->query("
    SELECT
        room_usage.*,
        rooms.room_name,
        teachers.employee_id
    FROM room_usage
    LEFT JOIN rooms    ON room_usage.room_id    = rooms.id
    LEFT JOIN teachers ON room_usage.teacher_id = teachers.id
    ORDER BY room_usage.start_time DESC
");
$roomUsage = $stmt->fetchAll();

// ======================================
// GET TEACHER SCHEDULES
// ======================================
$stmt = $pdo->query("
    SELECT
        teacher_schedules.*,
        teachers.full_name AS teacher_name,
        rooms.room_name
    FROM teacher_schedules
    LEFT JOIN teachers ON teacher_schedules.teacher_id = teachers.id
    LEFT JOIN rooms    ON teacher_schedules.room_id    = rooms.id
    ORDER BY teacher_schedules.created_at DESC
");
$schedules = $stmt->fetchAll();

// ======================================
// GROUP ROOM USAGE BY DATE
// ======================================
$usageByDate = [];
foreach ($roomUsage as $u) {
    $dateKey = date('Y-m-d', strtotime($u['start_time']));
    $usageByDate[$dateKey][] = $u;
}
ksort($usageByDate);

// ======================================
// COMPUTE ANALYTICS FOR CHARTS
// ======================================
$roomUsageCounts  = [];
$roomUsageMinutes = [];
$teacherCount     = 0;
$studentCount     = 0;
$dayOfWeekCounts  = array_fill(0, 7, 0);

foreach ($roomUsage as $u) {
    $name = $u['room_name'] ?? 'Unknown';

    $roomUsageCounts[$name]  = ($roomUsageCounts[$name]  ?? 0) + 1;

    $mins = max(0, (strtotime($u['end_time']) - strtotime($u['start_time'])) / 60);
    $roomUsageMinutes[$name] = ($roomUsageMinutes[$name] ?? 0) + $mins;

    if ($u['user_type'] === 'teacher') {
        $teacherCount++;
    } else {
        $studentCount++;
    }

    $dow = (int) date('w', strtotime($u['start_time']));
    $dayOfWeekCounts[$dow]++;
}

arsort($roomUsageCounts);

$roomHours = [];
foreach ($roomUsageMinutes as $name => $mins) {
    $roomHours[$name] = round($mins / 60, 1);
}
arsort($roomHours);

$topRooms     = array_slice($roomUsageCounts, 0, 10, true);
$topRoomHours = array_slice($roomHours,       0, 10, true);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/adminstyle.css">
</head>

<body>

    <div class="layout">

        <!-- ===================================== -->
        <!-- SIDEBAR -->
        <!-- ===================================== -->
        <aside class="sidebar">
            <div class="sidebar-logo">SMART CAMPUS</div>
            <p class="sidebar-label">Navigation</p>
            <nav class="sidebar-nav">
                <a href="#overview">Overview</a>
                <a href="#pending">Pending Requests</a>
                <a href="#approved">Approved Students</a>
                <a href="#denied">Denied Requests</a>
                <a href="#usage">Room Usage</a>
                <a href="#rooms">Manage Rooms</a>
                <a href="#teachers">Manage Teachers</a>
                <a href="#schedules">Class Schedules</a>
            </nav>
            <a href="logout.php" class="sidebar-logout">Logout</a>
        </aside>

        <!-- ===================================== -->
        <!-- MAIN -->
        <!-- ===================================== -->
        <main class="main">

            <!-- STICKY FLASH MESSAGES -->
            <?php if ($message || $error): ?>
            <div class="flash-stack">
                <?php if ($message): ?>
                    <div class="success-box">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-box">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="admin-header" id="overview">
                <div>
                    <h1>Room Availability Admin</h1>
                    <p>Review student room authorization requests.</p>
                </div>
            </div>

            <!-- STAT CARDS -->
            <div class="stat-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= count($pendingRequests) ?></span>
                    <span class="stat-label">Pending requests</span>
                    <span class="stat-dot dot-yellow"></span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= count($approvedStudents) ?></span>
                    <span class="stat-label">Approved students</span>
                    <span class="stat-dot dot-green"></span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= count($deniedRequests) ?></span>
                    <span class="stat-label">Denied requests</span>
                    <span class="stat-dot dot-red"></span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= count($roomUsage) ?></span>
                    <span class="stat-label">Room usage records</span>
                    <span class="stat-dot dot-purple"></span>
                </div>
            </div>

            <!-- ===================================== -->
            <!-- PENDING REQUESTS -->
            <!-- ===================================== -->
            <section class="admin-section" id="pending">
                <h2>Pending Authorization Requests</h2>

                <?php if (!$pendingRequests): ?>
                <p>No pending requests.</p>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Section</th>
                                <th>Room</th>
                                <th>Purpose</th>
                                <th>Requested Date</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Requested At</th>
                                <th>Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRequests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['student_id_number']) ?></td>
                                <td><?= htmlspecialchars($request['full_name']) ?></td>
                                <td><?= htmlspecialchars($request['section']) ?></td>
                                <td><?= htmlspecialchars($request['room_name'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($request['purpose']) ?></td>
                                <td><?= date('M d, Y', strtotime($request['valid_from'])) ?></td>
                                <td><?= date('g:i A', strtotime($request['valid_from'])) ?></td>
                                <td><?= date('g:i A', strtotime($request['valid_until'])) ?></td>
                                <td><?= date('M d, Y g:i A', strtotime($request['requested_at'])) ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <form method="POST" onsubmit="return confirm('Approve this request?');">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <button type="submit" name="approve_request" value="1" class="approve-button">
                                                Approve
                                            </button>
                                        </form>

                                        <form method="POST" onsubmit="return confirm('Deny this request?');">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <button type="submit" name="deny_request" value="1" class="danger-button">
                                                Deny
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </section>

            <!-- ===================================== -->
            <!-- APPROVED STUDENTS -->
            <!-- ===================================== -->
            <section class="admin-section" id="approved">
                <h2>Approved Students</h2>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Section</th>
                                <th>Room</th>
                                <th>Purpose</th>
                                <th>Valid From</th>
                                <th>Valid Until</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approvedStudents as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['student_id_number']) ?></td>
                                <td><?= htmlspecialchars($student['full_name']) ?></td>
                                <td><?= htmlspecialchars($student['section']) ?></td>
                                <td><?= htmlspecialchars($student['room_name'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($student['purpose']) ?></td>
                                <td><?= date('M d, Y g:i A', strtotime($student['valid_from'])) ?></td>
                                <td><?= date('M d, Y g:i A', strtotime($student['valid_until'])) ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Revoke this authorization?');">
                                        <input type="hidden" name="request_id" value="<?= $student['id'] ?>">
                                        <button type="submit" name="revoke_authorization" value="1" class="danger-button">
                                            Revoke
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (!$approvedStudents): ?>
                            <tr>
                                <td colspan="8">No approved students.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ===================================== -->
            <!-- DENIED REQUESTS -->
            <!-- ===================================== -->
            <section class="admin-section" id="denied">
                <h2>Denied Requests</h2>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Room</th>
                                <th>Purpose</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deniedRequests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['student_id_number']) ?></td>
                                <td><?= htmlspecialchars($request['full_name']) ?></td>
                                <td><?= htmlspecialchars($request['room_name'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($request['purpose']) ?></td>
                                <td><?= date('M d, Y', strtotime($request['valid_from'])) ?></td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (!$deniedRequests): ?>
                            <tr>
                                <td colspan="5">No denied requests.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ===================================== -->
            <!-- ROOM USAGE -->
            <!-- ===================================== -->
            <section class="admin-section" id="usage">
                <h2>Room Usage</h2>

                <div class="report-toolbar" data-html2canvas-ignore="true">
                    <div class="tab-group">
                        <button type="button" id="btnVisual" class="tab-button active"
                                onclick="setUsageView('visual')">Visual</button>
                        <button type="button" id="btnTable" class="tab-button"
                                onclick="setUsageView('table')">Table</button>
                        <button type="button" id="btnCharts" class="tab-button"
                                onclick="setUsageView('charts')">📊 Charts</button>
                    </div>
                    <button type="button" class="approve-button" onclick="exportUsagePDF()">
                        📄 Download PDF
                    </button>
                </div>

                <div id="usage-report">

                    <!-- VISUAL VIEW -->
                    <div id="usage-visual-view">
                        <?php if (!$roomUsage): ?>
                            <p>No room usage records.</p>
                        <?php else: ?>
                            <?php foreach ($usageByDate as $date => $entries): ?>
                                <div class="timeline-day">
                                    <h3 class="timeline-date">
                                        <?= date('F j, Y (l)', strtotime($date)) ?>
                                    </h3>
                                    <?php foreach ($entries as $u):
                                        $now = time();
                                        $st  = strtotime($u['start_time']);
                                        $et  = strtotime($u['end_time']);
                                        $state = ($now >= $st && $now < $et) ? 'live'
                                                : (($st > $now) ? 'future' : 'past');
                                    ?>
                                    <div class="timeline-card state-<?= $state ?>">
                                        <div class="timeline-time">
                                            <span><?= date('g:i A', $st) ?></span>
                                            <span class="timeline-arrow">→</span>
                                            <span><?= date('g:i A', $et) ?></span>
                                        </div>
                                        <div class="timeline-body">
                                            <h4>
                                                <?= htmlspecialchars($u['room_name']) ?>
                                                · <?= htmlspecialchars($u['user_name']) ?>
                                            </h4>
                                            <p>
                                                <?= htmlspecialchars(ucfirst($u['user_type'])) ?>
                                                <?php if (!empty($u['section'])): ?>
                                                    · <?= htmlspecialchars($u['section']) ?>
                                                <?php endif; ?>
                                            </p>
                                            <p><?= htmlspecialchars($u['purpose']) ?></p>
                                        </div>
                                        <div class="timeline-action" data-html2canvas-ignore="true">
                                            <form method="POST"
                                                  onsubmit="return confirm('Cancel this room usage?');">
                                                <input type="hidden" name="usage_id"
                                                       value="<?= (int) $u['id'] ?>">
                                                <button type="submit" name="cancel_usage"
                                                        class="danger-button">Cancel</button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- TABLE VIEW -->
                    <div id="usage-table-view" style="display:none;">
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Room</th>
                                        <th>User</th>
                                        <th>Type</th>
                                        <th>ID</th>
                                        <th>Section</th>
                                        <th>Purpose</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roomUsage as $usage): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($usage['room_name']) ?></td>
                                        <td><?= htmlspecialchars($usage['user_name']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($usage['user_type'])) ?></td>
                                        <td>
                                            <?php if ($usage['user_type'] === 'student'): ?>
                                                <?= htmlspecialchars($usage['student_id_number']) ?>
                                            <?php elseif (!empty($usage['employee_id'])): ?>
                                                <?= htmlspecialchars($usage['employee_id']) ?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($usage['section'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($usage['purpose']) ?></td>
                                        <td><?= date('M d, Y g:i A', strtotime($usage['start_time'])) ?></td>
                                        <td><?= date('M d, Y g:i A', strtotime($usage['end_time'])) ?></td>
                                        <td>
                                            <form method="POST"
                                                  onsubmit="return confirm('Cancel this room usage?');">
                                                <input type="hidden" name="usage_id"
                                                       value="<?= (int) $usage['id'] ?>">
                                                <button type="submit" name="cancel_usage"
                                                        class="danger-button">Cancel</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (!$roomUsage): ?>
                                    <tr>
                                        <td colspan="9">No room usage records.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- CHARTS VIEW -->
                    <div id="usage-charts-view" style="display:none;">

                        <?php if (!$roomUsage): ?>
                            <p class="chart-empty">No room usage data yet — charts will appear once records exist.</p>
                        <?php else: ?>

                            <div class="charts-grid">

                                <div class="chart-card">
                                    <h3>Most Used Rooms</h3>
                                    <p>Total number of bookings per room</p>
                                    <div class="chart-canvas-wrap">
                                        <canvas id="chartRooms"></canvas>
                                    </div>
                                </div>

                                <div class="chart-card">
                                    <h3>Teacher vs Student</h3>
                                    <p>Share of bookings by user type</p>
                                    <div class="chart-canvas-wrap">
                                        <canvas id="chartUsers"></canvas>
                                    </div>
                                </div>

                            </div>

                            <div class="charts-grid charts-grid-full">

                                <div class="chart-card">
                                    <h3>Usage by Day of Week</h3>
                                    <p>Which weekdays are busiest</p>
                                    <div class="chart-canvas-wrap" style="min-height:240px;">
                                        <canvas id="chartDays"></canvas>
                                    </div>
                                </div>

                            </div>

                            <div class="charts-grid charts-grid-full">

                                <div class="chart-card">
                                    <h3>Total Hours Used per Room</h3>
                                    <p>Top 10 rooms by total booking hours</p>
                                    <div class="chart-canvas-wrap" style="min-height:280px;">
                                        <canvas id="chartHours"></canvas>
                                    </div>
                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>
            </section>

            <!-- ===================================== -->
            <!-- MANAGE ROOMS -->
            <!-- ===================================== -->
            <section class="admin-section" id="rooms">
                <h2>Manage Rooms</h2>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Room Name</th>
                                <th>Building</th>
                                <th>Capacity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td>
                                    <input type="text" name="room_name" class="room-input"
                                        form="room-form-<?= (int) $room['id'] ?>"
                                        value="<?= htmlspecialchars($room['room_name']) ?>" required>
                                </td>
                                <td>
                                    <input type="text" name="building" class="room-input"
                                        form="room-form-<?= (int) $room['id'] ?>"
                                        value="<?= htmlspecialchars($room['building']) ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="capacity" class="room-input room-input-small"
                                        form="room-form-<?= (int) $room['id'] ?>"
                                        value="<?= (int) $room['capacity'] ?>" min="1" max="1000" required>
                                </td>
                                <td>
                                    <form method="POST" id="room-form-<?= (int) $room['id'] ?>">
                                        <input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>">
                                        <button type="submit" name="update_room" value="1" class="approve-button">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (!$rooms): ?>
                            <tr>
                                <td colspan="4">No rooms found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ===================================== -->
            <!-- MANAGE TEACHERS -->
            <!-- ===================================== -->
            <section class="admin-section" id="teachers">
                <h2>Manage Teachers</h2>
                <p>Teachers sign in to book rooms with their Employee ID.</p>

                <h3 class="subsection-title">Add teacher</h3>

                <form method="POST" class="teacher-add-form">
                    <div class="edit-field">
                        <label for="new-employee-id">Employee ID</label>
                        <input type="text" id="new-employee-id" name="employee_id" class="edit-input"
                            placeholder="Example: T-001" maxlength="50" required>
                    </div>

                    <div class="edit-field">
                        <label for="new-full-name">Full name</label>
                        <input type="text" id="new-full-name" name="full_name" class="edit-input"
                            placeholder="Juan Dela Cruz" maxlength="100" required>
                    </div>

                    <div class="edit-field">
                        <label for="new-department">Department</label>
                        <input type="text" id="new-department" name="department" class="edit-input"
                            placeholder="Example: BSIS" maxlength="100" required>
                    </div>

                    <button type="submit" name="add_teacher" value="1" class="approve-button add-button">
                        Add teacher
                    </button>
                </form>

                <h3 class="subsection-title">Current teachers</h3>

                <div class="table-wrapper">
                    <table class="edit-table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <td>
                                    <input type="text" name="employee_id" class="edit-input"
                                        form="teacher-form-<?= (int) $teacher['id'] ?>"
                                        value="<?= htmlspecialchars($teacher['employee_id']) ?>" maxlength="50" required>
                                </td>
                                <td>
                                    <input type="text" name="full_name" class="edit-input"
                                        form="teacher-form-<?= (int) $teacher['id'] ?>"
                                        value="<?= htmlspecialchars($teacher['full_name']) ?>" maxlength="100" required>
                                </td>
                                <td>
                                    <input type="text" name="department" class="edit-input"
                                        form="teacher-form-<?= (int) $teacher['id'] ?>"
                                        value="<?= htmlspecialchars($teacher['department']) ?>" maxlength="100" required>
                                </td>
                                <td>
                                    <form method="POST" id="teacher-form-<?= (int) $teacher['id'] ?>">
                                        <input type="hidden" name="teacher_id" value="<?= (int) $teacher['id'] ?>">
                                        <button type="submit" name="update_teacher" value="1" class="approve-button">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (!$teachers): ?>
                            <tr>
                                <td colspan="4" class="empty-cell">No teachers yet. Add one above.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ===================================== -->
            <!-- MANAGE CLASS SCHEDULES -->
            <!-- ===================================== -->
            <section class="admin-section" id="schedules">
                <h2>Manage Class Schedules</h2>
                <p>Create recurring weekly bookings. The system expands them into individual sessions automatically — teachers don't need to check in every day.</p>

                <h3 class="subsection-title">Add recurring schedule</h3>

                <form method="POST" class="schedule-form">
                    <div class="edit-field">
                        <label for="sched-teacher">Teacher</label>
                        <select id="sched-teacher" name="teacher_id" class="edit-input" required>
                            <option value="">Select teacher…</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= (int) $t['id'] ?>">
                                    <?= htmlspecialchars($t['full_name']) ?>
                                    (<?= htmlspecialchars($t['employee_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="edit-field">
                        <label for="sched-room">Room</label>
                        <select id="sched-room" name="room_id" class="edit-input" required>
                            <option value="">Select room…</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= (int) $r['id'] ?>">
                                    <?= htmlspecialchars($r['room_name']) ?>
                                    — <?= htmlspecialchars($r['building']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="edit-field">
                        <label for="sched-day">Day of week</label>
                        <select id="sched-day" name="day_of_week" class="edit-input" required>
                            <option value="1">Monday</option>
                            <option value="2">Tuesday</option>
                            <option value="3">Wednesday</option>
                            <option value="4">Thursday</option>
                            <option value="5">Friday</option>
                            <option value="6">Saturday</option>
                            <option value="0">Sunday</option>
                        </select>
                    </div>

                    <div class="edit-field">
                        <label for="sched-start">Start time</label>
                        <input type="time" id="sched-start" name="start_time"
                               class="edit-input" required>
                    </div>

                    <div class="edit-field">
                        <label for="sched-end">End time</label>
                        <input type="time" id="sched-end" name="end_time"
                               class="edit-input" required>
                    </div>

                    <div class="edit-field">
                        <label for="sched-from">Valid from</label>
                        <input type="date" id="sched-from" name="valid_from"
                               class="edit-input" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="edit-field">
                        <label for="sched-until">Valid until</label>
                        <input type="date" id="sched-until" name="valid_until"
                               class="edit-input" required>
                    </div>

                    <div class="edit-field">
                        <label for="sched-section">Section</label>
                        <input type="text" id="sched-section" name="section"
                               class="edit-input" placeholder="BSIS 3B" maxlength="50">
                    </div>

                    <div class="edit-field">
                        <label for="sched-purpose">Purpose</label>
                        <select id="sched-purpose" name="purpose" class="edit-input" required>
                            <option>Class</option>
                            <option>Practice</option>
                            <option>Bootcamp</option>
                            <option>Meeting</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <button type="submit" name="add_schedule" value="1"
                            class="approve-button add-button">Generate schedule</button>
                </form>

                <h3 class="subsection-title">Active schedules</h3>

                <div class="table-wrapper">
                    <table class="edit-table">
                        <thead>
                            <tr>
                                <th>Teacher</th>
                                <th>Room</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Range</th>
                                <th>Section</th>
                                <th>Purpose</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                            foreach ($schedules as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['teacher_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($s['room_name'] ?? '—') ?></td>
                                <td><?= $dayNames[(int) $s['day_of_week']] ?></td>
                                <td><?= date('g:i A', strtotime($s['start_time'])) ?>
                                    – <?= date('g:i A', strtotime($s['end_time'])) ?></td>
                                <td><?= date('M j', strtotime($s['valid_from'])) ?>
                                    – <?= date('M j, Y', strtotime($s['valid_until'])) ?></td>
                                <td><?= htmlspecialchars($s['section'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($s['purpose']) ?></td>
                                <td>
                                    <form method="POST"
                                          onsubmit="return confirm('Delete this schedule? Future sessions will be cancelled.');">
                                        <input type="hidden" name="schedule_id"
                                               value="<?= (int) $s['id'] ?>">
                                        <button type="submit" name="delete_schedule" value="1"
                                                class="danger-button">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (!$schedules): ?>
                            <tr><td colspan="8" class="empty-cell">No schedules yet. Add one above.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function setUsageView(view) {
    document.getElementById('usage-visual-view').style.display = view === 'visual' ? '' : 'none';
    document.getElementById('usage-table-view').style.display  = view === 'table'  ? '' : 'none';
    document.getElementById('usage-charts-view').style.display = view === 'charts' ? '' : 'none';

    document.getElementById('btnVisual').classList.toggle('active', view === 'visual');
    document.getElementById('btnTable').classList.toggle('active',  view === 'table');
    document.getElementById('btnCharts').classList.toggle('active', view === 'charts');

    if (view === 'charts') {
        requestAnimationFrame(initCharts);
    }
}

function exportUsagePDF() {
    const el = document.getElementById('usage-report');
    html2pdf().set({
        margin: 10,
        filename: 'room-usage-report-' + new Date().toISOString().slice(0, 10) + '.pdf',
        image: { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 2, backgroundColor: '#ffffff', useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }).from(el).save();
}

const chartData = {
    rooms: {
        labels: <?= json_encode(array_keys($topRooms)) ?>,
        values: <?= json_encode(array_values($topRooms)) ?>
    },
    hours: {
        labels: <?= json_encode(array_keys($topRoomHours)) ?>,
        values: <?= json_encode(array_values($topRoomHours)) ?>
    },
    userTypes: {
        labels: ['Teacher', 'Student'],
        values: [<?= $teacherCount ?>, <?= $studentCount ?>]
    },
    days: {
        labels: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
        values: <?= json_encode(array_values($dayOfWeekCounts)) ?>
    }
};

let chartsInitialized = false;

function initCharts() {
    if (chartsInitialized) return;
    if (typeof Chart === 'undefined') return;
    if (!document.getElementById('chartRooms')) return;
    chartsInitialized = true;

    Chart.defaults.color = '#d8d1e8';
    Chart.defaults.font.family = "'DM Sans', Arial, sans-serif";
    Chart.defaults.font.size = 12;
    const gridColor = 'rgba(52, 44, 70, 0.7)';

    new Chart(document.getElementById('chartRooms'), {
        type: 'bar',
        data: {
            labels: chartData.rooms.labels,
            datasets: [{
                label: 'Bookings',
                data: chartData.rooms.values,
                backgroundColor: '#b9a3e8',
                hoverBackgroundColor: '#e7defa',
                borderRadius: 8,
                barThickness: 18
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#231d31',
                    titleColor: '#e7defa',
                    bodyColor: '#d8d1e8',
                    borderColor: '#b9a3e8',
                    borderWidth: 1,
                    padding: 10
                }
            },
            scales: {
                x: { grid: { color: gridColor }, ticks: { precision: 0, color: '#9288a8' } },
                y: { grid: { display: false }, ticks: { color: '#e7defa' } }
            }
        }
    });

    new Chart(document.getElementById('chartUsers'), {
        type: 'doughnut',
        data: {
            labels: chartData.userTypes.labels,
            datasets: [{
                data: chartData.userTypes.values,
                backgroundColor: ['#b9a3e8', '#6fcf97'],
                hoverBackgroundColor: ['#e7defa', '#8fdfa9'],
                borderColor: '#231d31',
                borderWidth: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 18, color: '#d8d1e8', boxWidth: 14, boxHeight: 14 }
                },
                tooltip: {
                    backgroundColor: '#231d31',
                    titleColor: '#e7defa',
                    bodyColor: '#d8d1e8',
                    borderColor: '#b9a3e8',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function (ctx) {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0) || 1;
                            const pct = Math.round((ctx.parsed / total) * 100);
                            return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('chartDays'), {
        type: 'bar',
        data: {
            labels: chartData.days.labels,
            datasets: [{
                label: 'Bookings',
                data: chartData.days.values,
                backgroundColor: '#e8c15a',
                hoverBackgroundColor: '#f2d789',
                borderRadius: 8,
                barThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#231d31',
                    titleColor: '#e7defa',
                    bodyColor: '#d8d1e8',
                    borderColor: '#e8c15a',
                    borderWidth: 1,
                    padding: 10
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#d8d1e8' } },
                y: { grid: { color: gridColor }, ticks: { precision: 0, color: '#9288a8' } }
            }
        }
    });

    new Chart(document.getElementById('chartHours'), {
        type: 'bar',
        data: {
            labels: chartData.hours.labels,
            datasets: [{
                label: 'Hours',
                data: chartData.hours.values,
                backgroundColor: '#ef7b87',
                hoverBackgroundColor: '#f5a6ae',
                borderRadius: 8,
                barThickness: 18
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#231d31',
                    titleColor: '#e7defa',
                    bodyColor: '#d8d1e8',
                    borderColor: '#ef7b87',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function (ctx) {
                            return ` ${ctx.parsed.x} hours`;
                        }
                    }
                }
            },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: '#9288a8' } },
                y: { grid: { display: false }, ticks: { color: '#e7defa' } }
            }
        }
    });
}
</script>

</body>

</html>