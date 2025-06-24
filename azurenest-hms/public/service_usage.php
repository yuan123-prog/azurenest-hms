<?php
require_once("../private/db_connect.php");
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager') {
    die("Access denied. Only managers can access this page.");
}

// Handle form submission
if (isset($_POST['add_service_usage'])) {
    $service_id = intval($_POST['service_id']);
    $booking_id = intval($_POST['booking_id']);
    $quantity = intval($_POST['quantity']);
    $usage_date = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO Service_Usage (booking_id, service_id, quantity, usage_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $booking_id, $service_id, $quantity, $usage_date);
    if ($stmt->execute()) {
        $message = "Service usage recorded successfully.";
    } else {
        $message = "Error: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all services for dropdown
$services = $conn->query("SELECT * FROM Services");

// Fetch all service usages for display
$usages = $conn->query("SELECT su.usage_id, su.booking_id, s.name, s.description, s.rate, su.quantity, su.usage_date FROM Service_Usage su JOIN Services s ON su.service_id = s.service_id ORDER BY su.usage_date DESC, su.usage_id DESC");

// Calculate total service revenue
$total_revenue = 0;
$revenue_result = $conn->query("SELECT SUM(su.quantity * s.rate) AS total_service_revenue FROM Service_Usage su JOIN Services s ON su.service_id = s.service_id");
if ($revenue_result && $row = $revenue_result->fetch_assoc()) {
    $total_revenue = $row['total_service_revenue'] ? $row['total_service_revenue'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Service Usage Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        h2 {
            margin-bottom: 24px;
        }

        form {
            margin-bottom: 32px;
        }

        label {
            display: block;
            margin-top: 12px;
        }

        input,
        select {
            padding: 6px 10px;
            margin-top: 4px;
            width: 100%;
            max-width: 350px;
        }

        button {
            margin-top: 16px;
            padding: 8px 24px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            text-align: left;
        }

        th {
            background: #f1f5f9;
        }

        .success {
            color: #15803d;
            margin-bottom: 12px;
        }

        .error {
            color: #b91c1c;
            margin-bottom: 12px;
        }

        .revenue {
            font-weight: bold;
            color: #0d9488;
            margin-top: 18px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Service Usage Management (Admin Only)</h2>
        <?php if (isset($message)): ?>
            <div class="<?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <label for="service_id">Service:</label>
            <select name="service_id" id="service_id" required onchange="updateServiceDetails()">
                <option value="">Select Service</option>
                <?php $services->data_seek(0);
                while ($row = $services->fetch_assoc()): ?>
                    <option value="<?= $row['service_id'] ?>"
                        data-description="<?= htmlspecialchars($row['description']) ?>" data-rate="<?= $row['rate'] ?>">
                        <?= htmlspecialchars($row['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <label>Description:</label>
            <input type="text" id="service_description" name="service_description" readonly>
            <label>Rate (PHP):</label>
            <input type="number" id="service_rate" name="service_rate" readonly>
            <label>Booking ID:</label>
            <input type="number" name="booking_id" required>
            <label>Quantity:</label>
            <input type="number" name="quantity" value="1" min="1" required>
            <button type="submit" name="add_service_usage">Add Service Usage</button>
        </form>
        <table>
            <tr>
                <th>ID</th>
                <th>Booking ID</th>
                <th>Service Name</th>
                <th>Description</th>
                <th>Rate</th>
                <th>Quantity</th>
                <th>Date</th>
                <th>Total</th>
            </tr>
            <?php while ($usage = $usages->fetch_assoc()): ?>
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
        </table>
        <div class="revenue">Total Service Revenue: ₱<?= number_format($total_revenue, 2) ?></div>
    </div>
    <script>
        function updateServiceDetails() {
            var select = document.getElementById('service_id');
            var desc = select.options[select.selectedIndex]?.getAttribute('data-description');
            var rate = select.options[select.selectedIndex]?.getAttribute('data-rate');
            document.getElementById('service_description').value = desc || '';
            document.getElementById('service_rate').value = rate || '';
        }
    </script>
</body>

</html>