<?php
session_start();
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
$role = $_SESSION['role'] ?? null;
if ($role !== 'Manager') {
    die("Access denied.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Receipts Audit - Revenue Transparency</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
        }

        .card {
            background: #fff;
            padding: 32px 40px;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            margin-bottom: 36px;
        }

        h2 {
            margin-bottom: 24px;
        }

        .section-title {
            margin-bottom: 18px;
            font-size: 1.2em;
            color: #2563eb;
            font-weight: 600;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 18px;
            color: #b45309;
            background: linear-gradient(90deg, #fef08a 60%, #fde047 100%);
            text-decoration: none;
            font-weight: 600;
            border-radius: 7px;
            padding: 8px 22px;
            box-shadow: 0 2px 8px #0001;
            border: 1px solid #fde047;
            transition: background 0.2s, color 0.2s;
        }

        .back-link:hover {
            background: linear-gradient(90deg, #fde047 60%, #facc15 100%);
            color: #a16207;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            text-align: left;
            font-size: 1em;
        }

        th {
            background: #f1f5f9;
            font-weight: 600;
        }

        tr:nth-child(even) td {
            background: #f9fafb;
        }

        .total-row td {
            background: #f0fdf4;
            font-weight: bold;
            color: #059669;
            border-top: 2px solid #059669;
        }

        .grand-total {
            font-size: 1.5em;
            font-weight: bold;
            color: #059669;
            background: #f0fdf4;
            padding: 18px 0;
            text-align: center;
            border-radius: 8px;
            margin-top: 18px;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="revenue.php" class="back-link">&larr; Back to Revenue Dashboard</a>
        <h2>All Receipts Audit (Payments & Service Usage)</h2>
        <div class="card">
            <div class="section-title">Room & Booking Payments</div>
            <table>
                <tr>
                    <th>Payment ID</th>
                    <th>Booking ID</th>
                    <th>Guest Name</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
                <?php
                $total_payments = 0;
                $payments = $conn->query("SELECT p.payment_id, p.booking_id, g.name AS guest_name, p.amount, p.payment_date, p.status FROM Payments p LEFT JOIN Bookings b ON p.booking_id = b.booking_id LEFT JOIN Guest g ON b.guest_id = g.guest_id WHERE p.status='Completed' ORDER BY p.payment_date DESC, p.payment_id DESC");
                while ($row = $payments->fetch_assoc()):
                    $total_payments += $row['amount']; ?>
                    <tr>
                        <td><?= $row['payment_id'] ?></td>
                        <td><?= $row['booking_id'] ?></td>
                        <td><?= htmlspecialchars($row['guest_name']) ?></td>
                        <td>₱<?= number_format($row['amount'], 2) ?></td>
                        <td><?= $row['payment_date'] ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
                <tr class="total-row">
                    <td colspan="3">Total Payments</td>
                    <td>₱<?= number_format($total_payments, 2) ?></td>
                    <td colspan="3"></td>
                </tr>
            </table>
        </div>
        <div class="card">
            <div class="section-title">Service Usage Receipts</div>
            <table>
                <tr>
                    <th>Usage ID</th>
                    <th>Booking ID</th>
                    <th>Service Name</th>
                    <th>Description</th>
                    <th>Rate</th>
                    <th>Quantity</th>
                    <th>Date</th>
                    <th>Total</th>
                </tr>
                <?php
                $total_services = 0;
                $usages = $conn->query("SELECT su.usage_id, su.booking_id, s.name, s.description, s.rate, su.quantity, su.usage_date FROM Service_Usage su JOIN Services s ON su.service_id = s.service_id ORDER BY su.usage_date DESC, su.usage_id DESC");
                while ($usage = $usages->fetch_assoc()):
                    $total_services += $usage['rate'] * $usage['quantity']; ?>
                    <tr>
                        <td><?= $usage['usage_id'] ?></td>
                        <td><?= $usage['booking_id'] ?></td>
                        <td><?= htmlspecialchars($usage['name']) ?></td>
                        <td><?= htmlspecialchars($usage['description']) ?></td>
                        <td>₱<?= number_format($usage['rate'], 2) ?></td>
                        <td><?= $usage['quantity'] ?></td>
                        <td><?= $usage['usage_date'] ?></td>
                        <td>₱<?= number_format($usage['rate'] * $usage['quantity'], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
                <tr class="total-row">
                    <td colspan="7">Total Service Usage</td>
                    <td>₱<?= number_format($total_services, 2) ?></td>
                </tr>
            </table>
        </div>
        <div class="grand-total">Grand Total Revenue<br>₱<?= number_format($total_payments + $total_services, 2) ?>
        </div>
    </div>
</body>

</html>