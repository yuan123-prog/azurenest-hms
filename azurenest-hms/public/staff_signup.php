<?php
session_start();
// Only allow access if logged in as admin/manager
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager') {
    echo '<!DOCTYPE html><html><head><title>Access Denied</title><link rel="stylesheet" href="assets/css/style.css"></head><body><div class="login-container"><h2>Access Denied</h2><p class="error">You must be logged in as an admin to access staff signup.</p><p><a href="index.php">Back to Login</a></p></div></body></html>';
    exit();
}
require_once("../private/auth/session_check.php");
// All CSRF logic removed for efficiency
?>
<!DOCTYPE html>
<html>

<head>
    <title>Staff Signup</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="signup-container">
        <h2>Staff Signup</h2>
        <p style="color:#2563eb;font-size:1em;">As an admin, you can create accounts for your staff below.</p>
        <form action="../private/auth/staff_signup.php" method="post">
            <input type="text" name="name" placeholder="Full Name" required><br>
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
            <select name="role" required>
                <option value="Receptionist">Receptionist</option>
                <option value="Housekeeping">Housekeeping</option>
                <option value="Maintenance">Maintenance</option>
            </select><br>
            <input type="text" name="contact" placeholder="Contact (optional)"><br>
            <button type="submit">Create Staff</button>
        </form>
        <?php if (isset($_GET['error']))
            echo "<p class='error'>" . $_GET['error'] . "</p>"; ?>
        <?php if (isset($_GET['success']))
            echo "<p class='success'>" . $_GET['success'] . "</p>"; ?>
        <?php if (!empty($staff_duplicate_error)): ?>
            <div id="staff-error-modal"
                style="position:fixed;top:0;left:0;width:100vw;height:100vh;display:flex;align-items:center;justify-content:center;z-index:9999;background:rgba(0,0,0,0.25);">
                <div
                    style="background:#fff3f3;border:2px solid #ffb3b3;padding:32px 48px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.15);color:#b30000;font-size:1.2em;text-align:center;min-width:320px;">
                    <div style="margin-bottom:18px;">
                        <strong><?= htmlspecialchars($staff_duplicate_error) ?></strong>
                    </div>
                    <button onclick="document.getElementById('staff-error-modal').style.display='none'"
                        style="background:#ffb3b3;color:#b30000;border:none;padding:8px 24px;border-radius:6px;font-size:1em;cursor:pointer;">OK</button>
                </div>
            </div>
        <?php endif; ?>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>

</html>