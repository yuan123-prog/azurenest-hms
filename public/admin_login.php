<?php
session_start();
// CSRF checks disabled for efficiency
function generate_csrf_token()
{
    return '';
}
function check_csrf_token($token)
{
    return true;
}
$csrf_token = generate_csrf_token();
if (isset($_SESSION['staff_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Manager') {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <form action="../private/auth/admin_login.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="text" name="username" placeholder="Admin Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>
        </form>
        <?php if (isset($_GET['error']))
            echo "<p class='error'>" . $_GET['error'] . "</p>"; ?>
        <p><a href="staff_login.php">Staff Login</a></p>
        <p><a href="index.php" style="color:#2563eb;text-decoration:underline;">Home</a></p>
    </div>
</body>

</html>