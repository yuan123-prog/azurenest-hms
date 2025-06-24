<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
$staff_id = $_SESSION['staff_id'];

// Fetch staff info
$stmt = $conn->prepare("SELECT name, username, role, contact FROM Staff WHERE staff_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$stmt->bind_result($name, $username, $role, $contact);
$stmt->fetch();
$stmt->close();

// Handle contact update
if (isset($_POST['update_contact'])) {
    $new_contact = trim($_POST['contact']);
    $stmt = $conn->prepare("UPDATE Staff SET contact=? WHERE staff_id=?");
    $stmt->bind_param("si", $new_contact, $staff_id);
    $stmt->execute();
    $stmt->close();
    // Audit log for contact update
    $details = "Contact updated: staff_id=$staff_id, new_contact=$new_contact";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'update_contact', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    header("Location: profile.php?success=Contact+updated");
    exit();
}
// Handle password change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $stmt = $conn->prepare("SELECT password FROM Staff WHERE staff_id=?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->bind_result($hashed);
    $stmt->fetch();
    $stmt->close();
    if (!password_verify($current, $hashed)) {
        header("Location: profile.php?error=Current+password+incorrect");
        exit();
    }
    if ($new !== $confirm) {
        header("Location: profile.php?error=Passwords+do+not+match");
        exit();
    }
    $new_hashed = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE Staff SET password=? WHERE staff_id=?");
    $stmt->bind_param("si", $new_hashed, $staff_id);
    $stmt->execute();
    $stmt->close();
    // Audit log for password change
    $details = "Password changed: staff_id=$staff_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'change_password', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    header("Location: profile.php?success=Password+changed");
    exit();
}
include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>My Profile</h2>
    <table style="max-width:400px;margin-bottom:24px;">
        <tr>
            <td><b>Name:</b></td>
            <td><?= htmlspecialchars($name) ?></td>
        </tr>
        <tr>
            <td><b>Username:</b></td>
            <td><?= htmlspecialchars($username) ?></td>
        </tr>
        <tr>
            <td><b>Role:</b></td>
            <td><?= htmlspecialchars($role) ?></td>
        </tr>
        <tr>
            <td><b>Contact:</b></td>
            <td><?= htmlspecialchars($contact) ?></td>
        </tr>
    </table>
    <form method="post" style="margin-bottom:24px;max-width:400px;">
        <label for="contact">Update Contact:</label>
        <input type="text" name="contact" id="contact" value="<?= htmlspecialchars($contact) ?>">
        <button type="submit" name="update_contact">Update Contact</button>
    </form>
    <form method="post" style="max-width:400px;">
        <label for="current_password">Current Password:</label>
        <input type="password" name="current_password" id="current_password" required>
        <label for="new_password">New Password:</label>
        <input type="password" name="new_password" id="new_password" required>
        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" name="confirm_password" id="confirm_password" required>
        <button type="submit" name="change_password">Change Password</button>
    </form>
</main>
<?php include "includes/footer.php"; ?>