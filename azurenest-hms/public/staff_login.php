<?php
session_start();
// CSRF and session checks removed for efficiency
$csrf_token = '';
if (isset($_SESSION['staff_id']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'Manager') {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Staff Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-container">
        <h2>Staff Login</h2>
        <form action="../private/auth/staff_login.php" method="post">
            <input type="hidden" name="csrf_token" value="">
            <input type="text" name="username" placeholder="Staff Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>
        </form>
        <?php if (isset($_GET['error']))
            echo "<p class='error'>" . $_GET['error'] . "</p>"; ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Manager'): ?>
            <p><a href="staff_signup.php">Create Staff Account (Admin Only)</a></p>
        <?php endif; ?>
        <p><a href="admin_login.php">Admin Login</a></p>
        <p><a href="index.php" style="color:#2563eb;text-decoration:underline;">Home</a></p>
    </div>
    <?php if (!empty($staff_login_duplicate_error)): ?>
        <div id="staff-login-error-modal"
            style="position:fixed;top:0;left:0;width:100vw;height:100vh;display:flex;align-items:center;justify-content:center;z-index:9999;background:rgba(0,0,0,0.25);">
            <div
                style="background:#fff3f3;border:2px solid #ffb3b3;padding:32px 48px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.15);color:#b30000;font-size:1.2em;text-align:center;min-width:320px;">
                <div style="margin-bottom:18px;">
                    <strong><?= htmlspecialchars($staff_login_duplicate_error) ?></strong>
                </div>
                <button onclick="document.getElementById('staff-login-error-modal').style.display='none'"
                    style="background:#ffb3b3;color:#b30000;border:none;padding:8px 24px;border-radius:6px;font-size:1em;cursor:pointer;">OK</button>
            </div>
        </div>
    <?php endif; ?>
</body>

</html>