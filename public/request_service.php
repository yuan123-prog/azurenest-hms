<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
$role = $_SESSION['role'] ?? null;
if ($role !== 'Manager' && $role !== 'Receptionist') {
    die("Access denied.");
}
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
if ($booking_id <= 0) {
    die("Invalid booking.");
}
// Fetch booking info
$booking = $conn->query("SELECT b.*, g.name AS guest_name FROM Bookings b LEFT JOIN Guest g ON b.guest_id = g.guest_id WHERE b.booking_id=$booking_id")->fetch_assoc();
if (!$booking)
    die("Booking not found.");
// Handle form submission
if (isset($_POST['add_service_usage'])) {
    $service_id = intval($_POST['service_id']);
    $quantity = intval($_POST['quantity']);
    $usage_date = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO Service_Usage (booking_id, service_id, quantity, usage_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $booking_id, $service_id, $quantity, $usage_date);
    if ($stmt->execute()) {
        header("Location: bookings.php?success=service_added");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
    $stmt->close();
}
// Fetch all services for dropdown
$services = $conn->query("SELECT * FROM Services");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Request Service for Booking #<?= $booking_id ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
        }

        .container {
            max-width: 480px;
            margin: 48px auto;
            background: #fff;
            padding: 36px 40px 32px 40px;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
        }

        h2 {
            margin-bottom: 18px;
            font-size: 1.5em;
            color: #2563eb;
            font-weight: 700;
        }

        .info {
            color: #2563eb;
            margin-bottom: 18px;
            font-size: 1.1em;
            font-weight: 500;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 6px;
            font-weight: 500;
            color: #334155;
        }

        select,
        input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 1em;
            background: #f8fafc;
            margin-bottom: 4px;
            transition: border 0.2s;
        }

        select:focus,
        input[type="number"]:focus {
            border: 1.5px solid #2563eb;
            outline: none;
        }

        button {
            margin-top: 24px;
            padding: 10px 32px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px #0001;
            transition: background 0.2s;
        }

        button:hover {
            background: #1d4ed8;
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

        .error {
            color: #b91c1c;
            margin-bottom: 12px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="bookings.php" class="back-link">&larr; Back to Bookings</a>
        <h2>Request Service for Booking #<?= $booking_id ?></h2>
        <div class="info">Guest: <strong><?= htmlspecialchars($booking['guest_name']) ?></strong></div>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <label for="service_id">Service</label>
            <select name="service_id" id="service_id" required>
                <option value="">Select Service</option>
                <?php while ($row = $services->fetch_assoc()): ?>
                    <option value="<?= $row['service_id'] ?>"> <?= htmlspecialchars($row['name']) ?>
                        (₱<?= number_format($row['rate'], 2) ?>)</option>
                <?php endwhile; ?>
            </select>
            <label for="quantity">Quantity</label>
            <input type="number" name="quantity" id="quantity" value="1" min="1" required>
            <button type="submit" name="add_service_usage">Add Service</button>
        </form>
    </div>
</body>

</html>