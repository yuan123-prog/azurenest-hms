<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Manager');

// Handle add task
if ($is_admin && isset($_POST['add_task'])) {
    $room_id = $_POST['room_id'];
    $staff_id = $_POST['staff_id'];
    $task_type = $_POST['task_type'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    $scheduled_date = $_POST['scheduled_date'];
    $stmt = $conn->prepare("INSERT INTO Housekeeping (room_id, staff_id, task_type, status, notes, scheduled_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $room_id, $staff_id, $task_type, $status, $notes, $scheduled_date);
    $stmt->execute();
    $stmt->close();
    // Audit log for housekeeping add
    $admin_id = $_SESSION['staff_id'];
    $details = "Housekeeping task added: room_id=$room_id, staff_id=$staff_id, type=$task_type, status=$status, date=$scheduled_date";
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
    $details = "Housekeeping task deleted: task_id=$task_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'delete_housekeeping', ?)");
    $log->bind_param("is", $admin_id, $details);
    $log->execute();
    $log->close();
    $conn->query("DELETE FROM Housekeeping WHERE task_id=$task_id");
    header("Location: housekeeping.php?success=Task+deleted");
    exit();
}
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
// Fetch tasks
$tasks = $conn->query("SELECT * FROM Housekeeping");

include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Housekeeping Tasks</h2>
    <?php if ($is_admin): ?>
        <form method="post" style="margin-bottom:20px;">
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
            <select name="task_type">
                <option value="Cleaning">Cleaning</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Restocking">Restocking</option>
            </select>
            <select name="status">
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
            </select>
            <input type="text" name="notes" placeholder="Notes">
            <input type="date" name="scheduled_date" required>
            <button type="submit" name="add_task">Add Task</button>
        </form>
    <?php endif; ?>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>ID</th>
            <th>Room</th>
            <th>Staff</th>
            <th>Type</th>
            <th>Status</th>
            <th>Notes</th>
            <th>Scheduled Date</th>
            <?php if ($is_admin): ?>
                <th>Action</th><?php endif; ?>
        </tr>
        <?php while ($task = $tasks->fetch_assoc()): ?>
            <tr>
                <td><?= $task['task_id'] ?></td>
                <td><?= isset($room_options[$task['room_id']]) ? htmlspecialchars($room_options[$task['room_id']]) : $task['room_id'] ?>
                </td>
                <td><?= isset($staff_options[$task['staff_id']]) ? htmlspecialchars($staff_options[$task['staff_id']]) : $task['staff_id'] ?>
                </td>
                <td><?= htmlspecialchars($task['task_type']) ?></td>
                <td><?= htmlspecialchars($task['status']) ?></td>
                <td><?= htmlspecialchars($task['notes']) ?></td>
                <td><?= htmlspecialchars($task['scheduled_date']) ?></td>
                <?php if ($is_admin): ?>
                    <td><a href="housekeeping.php?delete=<?= $task['task_id'] ?>"
                            onclick="return confirm('Delete this task?')">Delete</a></td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
<?php include "includes/footer.php"; ?>