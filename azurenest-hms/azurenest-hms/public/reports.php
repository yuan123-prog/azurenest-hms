<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");

// Get counts
$room_count = $conn->query("SELECT COUNT(*) as cnt FROM Rooms")->fetch_assoc()['cnt'];
$booking_count = $conn->query("SELECT COUNT(*) as cnt FROM Bookings")->fetch_assoc()['cnt'];
$guest_count = $conn->query("SELECT COUNT(*) as cnt FROM Guest")->fetch_assoc()['cnt'];
$staff_count = $conn->query("SELECT COUNT(*) as cnt FROM Staff")->fetch_assoc()['cnt'];
$revenue = $conn->query("SELECT IFNULL(SUM(amount),0) as total FROM Payments WHERE status='Completed'")->fetch_assoc()['total'];

// Handle database backup download (admin only)
$role = $_SESSION['role'] ?? null;
if ($role === 'Manager' && isset($_GET['backup']) && $_GET['backup'] === 'db') {
    // Use credentials from db_connect.php
    global $db, $user, $pass, $host;
    // Path to mysqldump (adjust if needed)
    $mysqldump = 'C:/xampp/mysql/bin/mysqldump.exe';
    if (!file_exists($mysqldump)) {
        $mysqldump = 'mysqldump'; // fallback to system path
    }
    $backup_file = 'azurenest_db_backup_' . date('Ymd_His') . '.sql';
    $cmd = "$mysqldump -h$host -u$user " . ($pass ? "-p$pass " : "") . "$db 2>&1";
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $backup_file . '"');
    passthru($cmd);
    exit();
}

include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Reports</h2>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;max-width:500px;margin:0 auto;">
        <tr style="background:#f1f5f9;">
            <th>Metric</th>
            <th>Value</th>
        </tr>
        <tr>
            <td>Total Rooms</td>
            <td><?= $room_count ?></td>
        </tr>
        <tr>
            <td>Total Bookings</td>
            <td><?= $booking_count ?></td>
        </tr>
        <tr>
            <td>Total Guests</td>
            <td><?= $guest_count ?></td>
        </tr>
        <tr>
            <td>Total Staff</td>
            <td><?= $staff_count ?></td>
        </tr>
        <tr>
            <td>Total Revenue</td>
            <td>₱<?= number_format($revenue, 2) ?></td>
        </tr>
    </table>
    <?php if ($role === 'Manager'): ?>
        <a href="reports.php?backup=db" class="login-btn"
            style="margin-top:24px;display:inline-block;width:auto;padding:10px 24px;">Download Database Backup</a>
    <?php endif; ?>
</main>
<?php include "includes/footer.php"; ?>