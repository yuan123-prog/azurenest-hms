<?php
session_start();
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
// Only allow access if not logged in or if admin
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'Manager') {
    header("Location: dashboard.php");
    exit();
}

// Fetch room types for dropdown
$room_types = [];
$result = $conn->query("SELECT type_id, type_name, rate FROM Room_Types");
while ($row = $result->fetch_assoc()) {
    $room_types[] = $row;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Administrator Signup</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-container">
        <h2>Admin Signup</h2>
        <form action="../private/auth/admin_signup.php" method="post">
            <input type="text" name="name" placeholder="Full Name" required><br>
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
            <input type="text" name="contact" placeholder="Contact (optional)"><br>
            <label for="type_id">Room Type</label>
            <select name="type_id" id="type_id" required>
                <option value="">Select Type</option>
                <?php foreach ($room_types as $type): ?>
                    <option value="<?= $type['type_id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Create Admin</button>
        </form>
        <?php if (isset($_GET['error']))
            echo "<p class='error'>" . $_GET['error'] . "</p>"; ?>
        <?php if (isset($_GET['success']))
            echo "<p class='success'>" . $_GET['success'] . "</p>"; ?>
        <p><a href="index.php">Back to Login</a></p>
    </div>
</body>

</html>