<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
$role = $_SESSION['role'] ?? null;
if ($role !== 'Manager') {
    header("Location: dashboard.php?error=access_denied");
    exit();
}
$logs = $conn->query("SELECT Audit_Log.*, Staff.name FROM Audit_Log LEFT JOIN Staff ON Audit_Log.staff_id = Staff.staff_id ORDER BY timestamp DESC LIMIT 200");
include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Audit Log</h2>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>ID</th>
            <th>Staff</th>
            <th>Action</th>
            <th>Details</th>
            <th>Time</th>
        </tr>
        <?php while ($log = $logs->fetch_assoc()): ?>
            <tr>
                <td><?= $log['Audit_Log_ID'] ?></td>
                <td><?= htmlspecialchars($log['name'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($log['action']) ?></td>
                <td><?= htmlspecialchars($log['details']) ?></td>
                <td><?= htmlspecialchars($log['timestamp']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
<?php include "includes/footer.php"; ?>