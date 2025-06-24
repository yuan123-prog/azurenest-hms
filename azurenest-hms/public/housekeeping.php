<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Manager');

// Handle add task
if (isset($_POST['add_task'])) {
    $room_id = $_POST['room_id'];
    $staff_id = $_POST['staff_id'];
    $task_status = $_POST['remarks']; // Use the form's 'remarks' as 'task_status' in DB
    $maintenance_issue = isset($_POST['maintenance_issue']) ? $_POST['maintenance_issue'] : '';
    $scheduled_date = $_POST['scheduled_date'];
    $last_updated_by = (isset($_SESSION['role']) && $_SESSION['role'] === 'Housekeeping') ? $staff_id : null;
    $stmt = $conn->prepare("INSERT INTO housekeeping_tasks (room_id, staff_id, task_status, maintenance_issue, scheduled_date, last_updated_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssi", $room_id, $staff_id, $task_status, $maintenance_issue, $scheduled_date, $last_updated_by);
    $stmt->execute();
    $stmt->close();
    // Audit log for housekeeping add
    $admin_id = $_SESSION['staff_id'];
    $details = "Housekeeping task added: room_id=$room_id, staff_id=$staff_id, task_status=$task_status, date=$scheduled_date";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'add_housekeeping', ?)");
    $log->bind_param("is", $admin_id, $details);
    $log->execute();
    $log->close();
    header("Location: housekeeping.php?success=Task+added");
    exit();
}
// Handle delete task
if ($is_admin && isset($_GET['delete'])) {
    $task_id = intval($_GET['delete']);
    // Audit log before delete
    $admin_id = $_SESSION['staff_id'];
    $details = "Housekeeping task deleted: id=$task_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'delete_housekeeping', ?)");
    $log->bind_param("is", $admin_id, $details);
    $log->execute();
    $log->close();
    $conn->query("DELETE FROM housekeeping_tasks WHERE id=$task_id");
    header("Location: housekeeping.php?success=Task+deleted");
    exit();
}
// Handle edit task (admin only)
if ($is_admin && isset($_POST['edit_task'])) {
    $task_id = intval($_POST['task_id']);
    $room_id = $_POST['room_id'];
    $staff_id = $_POST['staff_id'];
    $task_type = $_POST['task_type'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    $scheduled_date = $_POST['scheduled_date'];
    $stmt = $conn->prepare("UPDATE housekeeping_tasks SET room_id=?, staff_id=?, task_type=?, status=?, notes=?, scheduled_date=? WHERE task_id=?");
    $stmt->bind_param("iissssi", $room_id, $staff_id, $task_type, $status, $notes, $scheduled_date, $task_id);
    $stmt->execute();
    $stmt->close();
    // Audit log for housekeeping edit
    $admin_id = $_SESSION['staff_id'];
    $details = "Housekeeping task edited: task_id=$task_id, room_id=$room_id, staff_id=$staff_id, type=$task_type, status=$status, date=$scheduled_date";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'edit_housekeeping', ?)");
    $log->bind_param("is", $admin_id, $details);
    $log->execute();
    $log->close();
    header("Location: housekeeping.php?success=Task+updated");
    exit();
}
// Remove the automatic update that sets all cleaning_status to 'Pending' on every page load
// $conn->query("UPDATE housekeeping_tasks SET cleaning_status = 'Pending' WHERE cleaning_status IS NULL OR TRIM(cleaning_status) = ''");
// Fetch rooms
$rooms = $conn->query("SELECT * FROM Rooms");
$room_options = [];
while ($row = $rooms->fetch_assoc()) {
    $room_options[$row['room_id']] = $row['room_number'];
}
// Fetch staff
$staff = $conn->query("SELECT * FROM Staff");
$staff_options = [];
while ($row = $staff->fetch_assoc()) {
    $staff_options[$row['staff_id']] = $row['name'];
}

// Fetch staff names for 'Last Updated By'
$staff_names = [];
$staff_result = $conn->query("SELECT staff_id, name FROM Staff");
if ($staff_result) {
    while ($s = $staff_result->fetch_assoc()) {
        $staff_names[$s['staff_id']] = $s['name'];
    }
}

// Fetch tasks
if ($is_admin) {
    $tasks = $conn->query("SELECT ht.*, r.room_number, s.name AS staff_name FROM housekeeping_tasks ht LEFT JOIN Rooms r ON ht.room_id = r.room_id LEFT JOIN Staff s ON ht.staff_id = s.staff_id");
} else {
    $staff_id = isset($_SESSION['staff_id']) ? intval($_SESSION['staff_id']) : 0;
    $tasks = $conn->query("SELECT ht.*, r.room_number, s.name AS staff_name FROM housekeeping_tasks ht LEFT JOIN Rooms r ON ht.room_id = r.room_id LEFT JOIN Staff s ON ht.staff_id = s.staff_id WHERE ht.staff_id = $staff_id");
}

include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Housekeeping Tasks</h2>
    <?php if ($is_admin): ?>
        <form method="post" style="margin-bottom:20px;" id="add-task-form">
            <select name="room_id" required>
                <option value="">Select Room</option>
                <?php foreach ($room_options as $id => $number): ?>
                    <option value="<?= $id ?>">Room <?= htmlspecialchars($number) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="staff_id" required>
                <option value="">Assign Staff</option>
                <?php foreach ($staff_options as $id => $name): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="maintenance_issue" id="maintenance-issue-select" required>
                <option value="Clean">Clean</option>
                <option value="Dirty">Dirty</option>
                <option value="Cleaning in Progress">Cleaning in Progress</option>
                <option value="Inspected">Inspected</option>
                <option value="Needs Maintenance">Needs Maintenance</option>
            </select>
            <select name="remarks" required>
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
            </select>
            <input type="date" name="scheduled_date" required>
            <button type="submit" name="add_task">Add Task</button>
        </form>
    <?php endif; ?>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>Room #</th>
            <th>Maintenance Issue</th>
            <th>Staff Assigned</th>
            <th>Remarks</th>
            <th>Actions</th>
        </tr>
        <?php while ($task = $tasks->fetch_assoc()): ?>
            <tr>
                <td><?= isset($task['room_number']) && $task['room_number'] !== ''
                    ? htmlspecialchars($task['room_number'])
                    : (isset($room_options[$task['room_id']]) ? htmlspecialchars($room_options[$task['room_id']]) : '-') ?>
                </td>
                <td><?= isset($task['maintenance_issue']) && trim($task['maintenance_issue']) !== ''
                    ? htmlspecialchars($task['maintenance_issue'])
                    : '-' ?>
                </td>
                <td><?= isset($staff_names[$task['staff_id']])
                    ? htmlspecialchars($staff_names[$task['staff_id']])
                    : (isset($task['staff_id']) ? htmlspecialchars($task['staff_id']) : '-') ?>
                </td>
                <td><?= isset($task['task_status']) && trim($task['task_status']) !== ''
                    ? htmlspecialchars($task['task_status'])
                    : '-' ?>
                </td>
                <td>
                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'Housekeeping' || $_SESSION['role'] === 'Manager')): ?>
                        <a
                            href="housekeeping.php?edit=<?= isset($task['id']) ? $task['id'] : (isset($task['task_id']) ? $task['task_id'] : '') ?>">Edit</a>
                        |
                        <a href="housekeeping.php?delete=<?= isset($task['id']) ? $task['id'] : (isset($task['task_id']) ? $task['task_id'] : '') ?>"
                            onclick="return confirm('Delete this task?')">Delete</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
<?php include "includes/footer.php"; ?>