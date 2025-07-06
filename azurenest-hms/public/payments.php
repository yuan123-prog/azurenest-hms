<?php
session_start();
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
$role = $_SESSION['role'] ?? null;
if ($role !== 'Manager') {
    header("Location: dashboard.php?error=access_denied");
    exit();
}
// Handle status update
if (isset($_POST['set_completed']) && isset($_POST['payment_id'])) {
    $payment_id = intval($_POST['payment_id']);
    $conn->query("UPDATE Payments SET status='Completed' WHERE payment_id=$payment_id");
    header("Location: payments.php?success=updated");
    exit();
}
// Handle delete payment (allow delete for any status)
if (isset($_POST['delete_payment']) && isset($_POST['payment_id'])) {
    $payment_id = intval($_POST['payment_id']);
    $conn->query("DELETE FROM Payments WHERE payment_id=$payment_id");
    header("Location: payments.php?success=deleted");
    exit();
}
$payments = $conn->query("SELECT p.*, g.name AS guest_name, b.booking_id FROM Payments p LEFT JOIN Bookings b ON p.booking_id = b.booking_id LEFT JOIN Guest g ON b.guest_id = g.guest_id ORDER BY p.payment_date DESC");

// Fetch service usage records
$services = $conn->query("
    SELECT su.usage_id, su.booking_id, g.name AS guest_name, s.name AS service_name, su.quantity, s.rate, (su.quantity * s.rate) AS total, su.usage_date
    FROM Service_Usage su
    LEFT JOIN Bookings b ON su.booking_id = b.booking_id
    LEFT JOIN Guest g ON b.guest_id = g.guest_id
    LEFT JOIN Services s ON su.service_id = s.service_id
    ORDER BY su.usage_date DESC
");
include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Payments Management</h2>
    <?php if (isset($_GET['success'])): ?>
        <div style="color:green;margin-bottom:12px;">
            <?php if ($_GET['success'] === 'updated'): ?>Payment status updated!<?php elseif ($_GET['success'] === 'deleted'): ?>Payment deleted!<?php endif; ?>
        </div>
    <?php endif; ?>
    <h3>Room & Booking Payments</h3>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;margin-bottom:32px;">
        <tr style="background:#f1f5f9;">
            <th>Payment ID</th>
            <th>Booking ID</th>
            <th>Guest Name</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $payments->fetch_assoc()): ?>
            <tr>
                <td><?= $row['payment_id'] ?></td>
                <td><?= $row['booking_id'] ?></td>
                <td><?= htmlspecialchars($row['guest_name'] ?? 'N/A') ?></td>
                <td>₱<?= number_format($row['amount'], 2) ?></td>
                <td><?= $row['payment_date'] ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td style="vertical-align:middle;">
                    <div style="display:flex;gap:8px;align-items:center;justify-content:center;">
                        <?php if ($row['status'] !== 'Completed' && $row['status'] !== 'Cancelled'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                <button type="submit" name="set_completed" style="padding:4px 10px;border-radius:4px;border:1px solid #059669;background:#fff;color:#059669;font-weight:600;cursor:pointer;">Set Completed</button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this payment?');">
                                <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                <button type="submit" name="delete_payment" style="color:#fff;background:#b91c1c;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;">Delete</button>
                            </form>
                        <?php elseif ($row['status'] === 'Completed'): ?>
                            <span style="color:green;font-weight:bold;">Completed</span>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this payment?');">
                                <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                <button type="submit" name="delete_payment" style="color:#fff;background:#b91c1c;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;">Delete</button>
                            </form>
                        <?php elseif ($row['status'] === 'Cancelled'): ?>
                            <span style="color:#b91c1c;font-weight:bold;">Cancelled</span>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this payment?');">
                                <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                <button type="submit" name="delete_payment" style="color:#fff;background:#b91c1c;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h3>Service Usage</h3>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>Usage ID</th>
            <th>Booking ID</th>
            <th>Guest Name</th>
            <th>Service</th>
            <th>Quantity</th>
            <th>Rate</th>
            <th>Total</th>
            <th>Date</th>
        </tr>
        <?php while ($row = $services->fetch_assoc()): ?>
            <tr>
                <td><?= $row['usage_id'] ?></td>
                <td><?= $row['booking_id'] ?></td>
                <td><?= htmlspecialchars($row['guest_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['service_name'] ?? 'N/A') ?></td>
                <td><?= $row['quantity'] ?></td>
                <td>₱<?= number_format($row['rate'], 2) ?></td>
                <td>₱<?= number_format($row['total'], 2) ?></td>
                <td><?= $row['usage_date'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
<?php include "includes/footer.php"; ?>