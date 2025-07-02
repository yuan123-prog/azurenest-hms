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
// Fetch guests for dropdown
$guest_list = $conn->query("SELECT guest_id, name FROM Guest ORDER BY name ASC");
include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Guest Communication Center</h2>
    <?php if (isset($_GET['success'])): ?>
        <div
            style="background:#d1fae5;color:#065f46;padding:10px 18px;border-radius:6px;margin-bottom:16px;max-width:500px;">
            <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div
            style="background:#fee2e2;color:#991b1b;padding:10px 18px;border-radius:6px;margin-bottom:16px;max-width:500px;">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>
    <?php if ($can_edit): ?>
        <form method="post" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <select name="guest_name" required
                style="padding:6px 10px;border-radius:5px;border:1px solid #ccc;min-width:180px;">
                <option value="">Select Guest</option>
                <?php while ($g = $guest_list->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($g['name']) ?>"><?= htmlspecialchars($g['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <input type="text" name="message" placeholder="Message" required
                style="flex:1;min-width:180px;padding:6px 10px;border-radius:5px;border:1px solid #ccc;">
            <input type="date" name="date" required style="padding:6px 10px;border-radius:5px;border:1px solid #ccc;">
            <button type="submit" name="add_message"
                style="background:#059669;color:#fff;padding:7px 18px;border:none;border-radius:6px;font-weight:500;">Add
                Message</button>
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