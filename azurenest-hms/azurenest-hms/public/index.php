<?php
session_start();
require_once("../private/db_connect.php");

// Check if an admin exists
$admin_exists = false;
$sql = "SELECT COUNT(*) as cnt FROM Staff WHERE role='Manager'";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $admin_exists = ($row['cnt'] > 0);
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Hotel Login Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-main-wrapper">
        <div class="login-container staff-login">
            <h2>Staff Login</h2>
            <a class="login-btn" href="staff_login.php">Go to Staff Login</a>
        </div>
        <div class="login-container admin-login">
            <h2>Admin Login</h2>
            <a class="login-btn" href="admin_login.php">Go to Admin Login</a>
            <?php if (!$admin_exists): ?>
                <a class="login-btn" href="admin_signup.php">Admin Signup</a>
                <p style="color:#b45309;font-size:0.98em;margin-top:8px;">First time setup: Create your admin account.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>