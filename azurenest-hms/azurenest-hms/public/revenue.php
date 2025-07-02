<?php
session_start();
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
$role = $_SESSION['role'] ?? null;
if ($role !== 'Manager') {
    header("Location: dashboard.php?error=access_denied");
    exit();
}
// Total revenue
$total = $conn->query("SELECT IFNULL(SUM(amount),0) as total FROM Payments WHERE status='Completed'")->fetch_assoc()['total'];
// Revenue by month (last 12 months)
$monthly = $conn->query("SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as total FROM Payments WHERE status='Completed' GROUP BY month ORDER BY month DESC LIMIT 12");
include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Revenue Dashboard</h2>
    <div style="background:#fff;padding:24px 32px;border-radius:10px;max-width:600px;margin:0 auto 32px auto;box-shadow:0 2px 8px #0001;">
        <h3>Total Revenue</h3>
        <div style="font-size:2em;font-weight:bold;color:#059669;">₱<?= number_format($total,2) ?></div>
    </div>
    <div style="background:#fff;padding:18px 24px;border-radius:10px;max-width:600px;margin:0 auto 32px auto;box-shadow:0 2px 8px #0001;">
        <h4>Revenue by Month (Last 12 Months)</h4>
        <table border="1" cellpadding="8" style="width:100%;background:#fff;">
            <tr style="background:#f1f5f9;"><th>Month</th><th>Total</th></tr>
            <?php while($row = $monthly->fetch_assoc()): ?>
                <tr><td><?= $row['month'] ?></td><td>₱<?= number_format($row['total'],2) ?></td></tr>
            <?php endwhile; ?>
        </table>
    </div>
</main>
<?php include "includes/footer.php"; ?>
