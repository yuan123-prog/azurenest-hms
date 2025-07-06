<?php
// Run this script ONCE to fix missing payments for bookings
require_once("../private/db_connect.php");
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Manager' && $_SESSION['role'] !== 'Admin')) {
    die('Access denied.');
}
$fixed = 0;
$bookings = $conn->query("SELECT b.booking_id, b.room_id, b.check_in, b.check_out, b.status, r.price FROM Bookings b LEFT JOIN Payments p ON b.booking_id = p.booking_id LEFT JOIN Rooms r ON b.room_id = r.room_id WHERE p.payment_id IS NULL");
while ($row = $bookings->fetch_assoc()) {
    $booking_id = $row['booking_id'];
    $room_price = floatval($row['price']);
    if ($room_price > 0) {
        $stmt = $conn->prepare("INSERT INTO Payments (booking_id, amount, status, payment_date) VALUES (?, ?, 'Completed', NOW())");
        $stmt->bind_param("id", $booking_id, $room_price);
        $stmt->execute();
        $stmt->close();
        $fixed++;
    }
}
echo "<h2>Fixed $fixed missing payment(s).</h2>";
echo '<a href="dashboard.php">Back to Dashboard</a>';
