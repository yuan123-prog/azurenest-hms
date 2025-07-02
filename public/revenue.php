<?php
session_start();
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
$role = $_SESSION['role'] ?? null;
if ($role !== 'Manager') {
    header("Location: dashboard.php?error=access_denied");
    exit();
}
// Total revenue: sum of payments and service usage
$payments_total = $conn->query("SELECT IFNULL(SUM(amount),0) as total FROM Payments WHERE status='Completed'")->fetch_assoc()['total'];
$services_total = $conn->query("SELECT IFNULL(SUM(su.quantity * s.rate),0) as total FROM Service_Usage su JOIN Services s ON su.service_id = s.service_id")->fetch_assoc()['total'];
$total = $payments_total + $services_total;
// Revenue by month (last 12 months, correct join for payments/services)
$monthly = $conn->query("
    SELECT
        m.month,
        IFNULL(p.payments, 0) AS payments,
        IFNULL(s.services, 0) AS services,
        (IFNULL(p.payments, 0) + IFNULL(s.services, 0)) AS total
    FROM (
        SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month FROM Payments WHERE status='Completed'
        UNION
        SELECT DATE_FORMAT(usage_date, '%Y-%m') AS month FROM Service_Usage
    ) m
    LEFT JOIN (
        SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS payments
        FROM Payments
        WHERE status='Completed'
        GROUP BY month
    ) p ON p.month = m.month
    LEFT JOIN (
        SELECT DATE_FORMAT(su.usage_date, '%Y-%m') AS month, SUM(su.quantity * s.rate) AS services
        FROM Service_Usage su
        JOIN Services s ON su.service_id = s.service_id
        GROUP BY month
    ) s ON s.month = m.month
    GROUP BY m.month
    ORDER BY m.month DESC
    LIMIT 12
");
include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Revenue Dashboard</h2>
    <div style="text-align:right;max-width:600px;margin:0 auto 16px auto;">
        <a href="all_receipts.php"
            style="display:inline-block;padding:8px 20px;background:#059669;color:#fff;border-radius:6px;text-decoration:none;font-weight:500;box-shadow:0 2px 8px #0001;">View
            All Receipts (Audit)</a>
    </div>
    <div
        style="background:#fff;padding:24px 32px;border-radius:10px;max-width:600px;margin:0 auto 32px auto;box-shadow:0 2px 8px #0001;">
        <h3>Total Revenue</h3>
        <div style="font-size:2em;font-weight:bold;color:#059669;">₱<?= number_format($total, 2) ?></div>
    </div>
    <div
        style="background:#fff;padding:18px 24px;border-radius:10px;max-width:600px;margin:0 auto 32px auto;box-shadow:0 2px 8px #0001;">
        <h4>Revenue by Month (Last 12 Months)</h4>
        <table border="1" cellpadding="8" style="width:100%;background:#fff;">
            <tr style="background:#f1f5f9;">
                <th>Month</th>
                <th>Room/Booking</th>
                <th>Services</th>
                <th>Total</th>
            </tr>
            <?php while ($row = $monthly->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['month'] ?></td>
                    <td>₱<?= number_format($row['payments'], 2) ?></td>
                    <td>₱<?= number_format($row['services'], 2) ?></td>
                    <td>₱<?= number_format($row['total'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

    </div>
</main>
<?php include "includes/footer.php"; ?>