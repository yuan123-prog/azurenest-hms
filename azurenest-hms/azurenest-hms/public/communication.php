<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
$role = $_SESSION['role'] ?? null;
$is_admin = ($role === 'Manager');
$is_receptionist = ($role === 'Receptionist');
$is_housekeeping = ($role === 'Housekeeping');
$is_maintenance = ($role === 'Maintenance');
$can_edit = ($is_admin || $is_receptionist);
$can_view = ($can_edit || $is_housekeeping || $is_maintenance);
if (!$can_view) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

// Create table if not exists (for demo)
$conn->query("CREATE TABLE IF NOT EXISTS Communication (id INT AUTO_INCREMENT PRIMARY KEY, guest_name VARCHAR(100), message TEXT, date DATE)");

// Handle add message
if ($can_edit && isset($_POST['add_message'])) {
    $guest_name = $_POST['guest_name'];
    $message = $_POST['message'];
    $date = $_POST['date'];
    $stmt = $conn->prepare("INSERT INTO Communication (guest_name, message, date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $guest_name, $message, $date);
    $stmt->execute();
    $stmt->close();
    // Audit log for communication add
    $staff_id = $_SESSION['staff_id'];
    $details = "Message added: guest_name=$guest_name, message=$message, date=$date";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'add_communication', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    header("Location: communication.php?success=Message+added");
    exit();
}
// Prevent Housekeeping and Maintenance from posting add/delete
if (($is_housekeeping || $is_maintenance) && (isset($_POST['add_message']) || isset($_GET['delete']))) {
    header("Location: communication.php?error=access_denied");
    exit();
}
// Handle delete message
if ($is_admin && isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Audit log before delete
    $staff_id = $_SESSION['staff_id'];
    $details = "Message deleted: id=$id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'delete_communication', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    $conn->query("DELETE FROM Communication WHERE id=$id");
    header("Location: communication.php?success=Message+deleted");
    exit();
}
// Fetch messages
$messages = $conn->query("SELECT * FROM Communication ORDER BY date DESC, id DESC");
include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Guest Communication Center</h2>
    <?php if ($can_edit): ?>
        <form method="post" style="margin-bottom:20px;">
            <input type="text" name="guest_name" placeholder="Guest Name" required>
            <input type="text" name="message" placeholder="Message" required>
            <input type="date" name="date" required>
            <button type="submit" name="add_message">Add Message</button>
        </form>
    <?php endif; ?>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>ID</th>
            <th>Guest Name</th>
            <th>Message</th>
            <th>Date</th>
            <?php if ($is_admin): ?>
                <th>Action</th><?php endif; ?>
        </tr>
        <?php while ($msg = $messages->fetch_assoc()): ?>
            <tr>
                <td><?= $msg['id'] ?></td>
                <td><?= htmlspecialchars($msg['guest_name']) ?></td>
                <td><?= htmlspecialchars($msg['message']) ?></td>
                <td><?= htmlspecialchars($msg['date']) ?></td>
                <?php if ($is_admin): ?>
                    <td><a href="communication.php?delete=<?= $msg['id'] ?>"
                            onclick="return confirm('Delete this message?')">Delete</a></td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
<?php include "includes/footer.php"; ?>