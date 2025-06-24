<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
$role = $_SESSION['role'] ?? null;
$staff_id = $_SESSION['staff_id'];

// Handle add task (admin/manager only)
if ($role === 'Manager' && isset($_POST['add_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $assigned_to = intval($_POST['assigned_to']);
    $stmt = $conn->prepare("INSERT INTO Tasks (title, description, assigned_to, assigned_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $title, $description, $assigned_to, $staff_id);
    $stmt->execute();
    $stmt->close();
    // Audit log
    $details = "Task assigned: title=$title, assigned_to=$assigned_to";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'add_task', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    header("Location: tasks.php?success=Task+assigned");
    exit();
}
// Handle status update (assigned staff)
if (isset($_POST['update_status'])) {
    $task_id = intval($_POST['task_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE Tasks SET status=? WHERE task_id=? AND assigned_to=?");
    $stmt->bind_param("sii", $status, $task_id, $staff_id);
    $stmt->execute();
    $stmt->close();
    // Audit log
    $details = "Task status updated: task_id=$task_id, status=$status";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'update_task_status', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    header("Location: tasks.php?success=Task+updated");
    exit();
}
// Fetch staff for assignment
$staff_list = $conn->query("SELECT staff_id, name, role FROM Staff");
$staff_options = [];
while ($row = $staff_list->fetch_assoc()) {
    $staff_options[$row['staff_id']] = $row['name'] . ' (' . $row['role'] . ')';
}
// Fetch tasks
if ($role === 'Manager') {
    $tasks = $conn->query("SELECT t.*, s.name AS assigned_name FROM Tasks t LEFT JOIN Staff s ON t.assigned_to = s.staff_id ORDER BY t.created_at DESC");
} else if ($role === 'Receptionist') {
    $tasks = $conn->query("SELECT t.*, s.name AS assigned_name FROM Tasks t LEFT JOIN Staff s ON t.assigned_to = s.staff_id WHERE t.assigned_to = $staff_id OR t.assigned_by = $staff_id ORDER BY t.created_at DESC");
} else {
    $tasks = $conn->query("SELECT t.*, s.name AS assigned_name FROM Tasks t LEFT JOIN Staff s ON t.assigned_to = s.staff_id WHERE t.assigned_to = $staff_id ORDER BY t.created_at DESC");
}
// Toast for new assigned task
if ($role !== 'Manager' && $role !== 'Receptionist') {
    $new_task = $conn->query("SELECT title FROM Tasks WHERE assigned_to=$staff_id AND status='Pending' AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 2 ORDER BY created_at DESC LIMIT 1");
    if ($new_task && $row = $new_task->fetch_assoc()) {
        echo '<div class="toast toast-success" id="toast">New Task Assigned: ' . htmlspecialchars($row['title']) . '</div>';
    }
}
include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Tasks & Notifications</h2>
    <?php if ($role === 'Manager'): ?>
        <form method="post" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <input type="text" name="title" placeholder="Task Title" required
                style="padding:6px 10px;border-radius:5px;border:1px solid #ccc;min-width:180px;">
            <input type="text" name="description" placeholder="Description"
                style="flex:1;min-width:180px;padding:6px 10px;border-radius:5px;border:1px solid #ccc;">
            <select name="assigned_to" required
                style="padding:6px 10px;border-radius:5px;border:1px solid #ccc;min-width:180px;">
                <option value="">Assign to...</option>
                <?php foreach ($staff_options as $id => $name): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="add_task"
                style="background:#059669;color:#fff;padding:7px 18px;border:none;border-radius:6px;font-weight:500;">Assign
                Task</button>
        </form>
    <?php endif; ?>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>ID</th>
            <th>Title</th>
            <th>Description</th>
            <th>Assigned To</th>
            <th>Status</th>
            <th>Created</th>
            <th>Action</th>
        </tr>
        <?php while ($task = $tasks->fetch_assoc()): ?>
            <tr>
                <?php if ($role === 'Manager' && isset($_GET['edit']) && $_GET['edit'] == $task['task_id']): ?>
                    <form method="post">
                        <td><?= $task['task_id'] ?><input type="hidden" name="task_id" value="<?= $task['task_id'] ?>"></td>
                        <td><input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required
                                style="width:120px;"></td>
                        <td><input type="text" name="description" value="<?= htmlspecialchars($task['description']) ?>"
                                style="width:180px;"></td>
                        <td><input type="text" name="assigned_to" value="<?= htmlspecialchars($task['assigned_to']) ?>" required
                                style="width:60px;"></td>
                        <td><input type="text" name="status" value="<?= htmlspecialchars($task['status']) ?>" required
                                style="width:90px;"></td>
                        <td><input type="text" name="created_at" value="<?= htmlspecialchars($task['created_at']) ?>" readonly
                                style="width:120px;"></td>
                        <td>
                            <button type="submit" name="edit_task">Save</button>
                            <a href="tasks.php">Cancel</a>
                        </td>
                    </form>
                <?php else: ?>
                    <td><?= $task['task_id'] ?></td>
                    <td><?= htmlspecialchars($task['title']) ?></td>
                    <td><?= htmlspecialchars($task['description']) ?></td>
                    <td><?= htmlspecialchars($task['assigned_name']) ?></td>
                    <td><?= htmlspecialchars($task['status']) ?></td>
                    <td><?= htmlspecialchars($task['created_at']) ?></td>
                    <td>
                        <?php if ($role === 'Manager'): ?>
                            <a href="tasks.php?edit=<?= $task['task_id'] ?>">Edit</a> |
                            <a href="tasks.php?delete=<?= $task['task_id'] ?>"
                                onclick="return confirm('Delete this task?')">Delete</a>
                        <?php elseif ($task['assigned_to'] == $staff_id): ?>
                            <form method="post" style="display:inline-flex;gap:6px;align-items:center;margin:0;">
                                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                <select name="status" style="padding:4px 8px;border-radius:4px;border:1px solid #ccc;">
                                    <option value="Pending" <?= $task['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="In Progress" <?= $task['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress
                                    </option>
                                    <option value="Completed" <?= $task['status'] == 'Completed' ? 'selected' : '' ?>>Completed
                                    </option>
                                </select>
                                <button type="submit" name="update_status"
                                    style="background:#2563eb;color:#fff;padding:4px 12px;border:none;border-radius:4px;font-size:0.95em;">Update</button>
                            </form>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
<?php include "includes/footer.php"; ?>