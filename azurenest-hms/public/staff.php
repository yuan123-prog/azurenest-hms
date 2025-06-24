<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Manager');
if (!$is_admin) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

// Handle delete staff
if ($is_admin && isset($_GET['delete'])) {
    $staff_id = intval($_GET['delete']);
    // Audit log before delete
    $admin_id = $_SESSION['staff_id'];
    $details = "Staff deleted: staff_id=$staff_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'delete_staff', ?)");
    $log->bind_param("is", $admin_id, $details);
    $log->execute();
    $log->close();
    $conn->query("DELETE FROM Staff WHERE staff_id=$staff_id");
    header("Location: staff.php?success=Staff+deleted");
    exit();
}

// Handle force logout
if ($is_admin && isset($_GET['force_logout'])) {
    $staff_id = intval($_GET['force_logout']);
    $conn->query("UPDATE Staff SET force_logout=1 WHERE staff_id=$staff_id");
    // Audit log for force logout
    $admin_id = $_SESSION['staff_id'];
    $details = "Force logout: staff_id=$staff_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'force_logout', ?)");
    $log->bind_param("is", $admin_id, $details);
    $log->execute();
    $log->close();
    header("Location: staff.php?success=Staff+will+be+logged+out+on+next+action");
    exit();
}

// Fetch staff
$staff = $conn->query("SELECT * FROM Staff");

include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Staff Management</h2>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>ID</th>
            <th>Name</th>
            <th>Username</th>
            <th>Role</th>
            <th>Contact</th>
            <th>Shift</th>
            <?php if ($is_admin): ?>
                <th>Action</th><?php endif; ?>
        </tr>
        <?php while ($row = $staff->fetch_assoc()): ?>
            <tr>
                <td><?= $row['staff_id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td><?= htmlspecialchars($row['contact']) ?></td>
                <td><?= htmlspecialchars($row['shift']) ?></td>
                <?php if ($is_admin): ?>
                    <td>
                        <a href="staff.php?delete=<?= $row['staff_id'] ?>"
                            onclick="return confirm('Delete this staff member?')">Delete</a>
                        |
                        <a href="staff.php?force_logout=<?= $row['staff_id'] ?>"
                            onclick="return confirm('Force logout this staff member?')">Force Logout</a>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
    <?php if ($is_admin): ?>
        <p style="margin-top:18px;">
            <a class="login-btn add-staff-btn" href="staff_signup.php">
                <span class="plus-icon" aria-hidden="true">&#43;</span> Add New Staff
            </a>
        </p>
    <?php endif; ?>
</main>
<?php include "includes/footer.php"; ?>