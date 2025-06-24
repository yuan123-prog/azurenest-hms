<?php
$role = $_SESSION['role'] ?? null;
$modules = [];
if ($role === 'Manager') {
    $modules = [
        'dashboard.php' => 'Dashboard',
        'rooms.php' => 'Rooms',
        'bookings.php' => 'Bookings',
        'guests.php' => 'Guests',
        'housekeeping.php' => 'Housekeeping',
        'services.php' => 'Services',
        'inventory.php' => 'Inventory',
        'staff.php' => 'Staff',
        'reports.php' => 'Reports',
        'revenue.php' => 'Revenue',
        'communication.php' => 'Communication',
        'tasks.php' => 'Tasks',
        'profile.php' => 'My Profile',
        'audit_log.php' => 'Audit Log',
        'staff_signup.php' => 'Staff Signup',
        'logout.php' => 'Logout'
    ];
} elseif ($role === 'Receptionist') {
    $modules = [
        'dashboard.php' => 'Dashboard',
        'rooms.php' => 'Rooms',
        'bookings.php' => 'Bookings',
        'guests.php' => 'Guests',
        'services.php' => 'Services',
        'communication.php' => 'Communication',
        'tasks.php' => 'Tasks',
        'profile.php' => 'My Profile',
        'logout.php' => 'Logout'
    ];
} elseif ($role === 'Housekeeping') {
    $modules = [
        'dashboard.php' => 'Dashboard',
        'housekeeping.php' => 'Housekeeping',
        'communication.php' => 'Communication',
        'tasks.php' => 'Tasks',
        'profile.php' => 'My Profile',
        'logout.php' => 'Logout'
    ];
} elseif ($role === 'Maintenance') {
    $modules = [
        'dashboard.php' => 'Dashboard',
        'rooms.php' => 'Rooms',
        'housekeeping.php' => 'Housekeeping',
        'inventory.php' => 'Inventory',
        'communication.php' => 'Communication',
        'tasks.php' => 'Tasks',
        'profile.php' => 'My Profile',
        'logout.php' => 'Logout'
    ];
}
$current = basename($_SERVER['PHP_SELF']);
// Notification badge for tasks
$task_count = 0;
if (isset($_SESSION['staff_id'])) {
    require_once("../private/db_connect.php");
    $sid = $_SESSION['staff_id'];
    $result = $conn->query("SELECT COUNT(*) as cnt FROM Tasks WHERE assigned_to=$sid AND status='Pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $task_count = $row['cnt'];
    }
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <span class="logo">AzureNest</span>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($modules as $file => $label): ?>
            <a href="<?= $file ?>" class="sidebar-link<?= $current === $file ? ' active' : '' ?>">
                <?= $label ?>
                <?php if ($file === 'tasks.php' && $task_count > 0): ?>
                    <span
                        style="background:#d32f2f;color:#fff;border-radius:50%;padding:2px 8px;font-size:0.9em;margin-left:8px;vertical-align:middle;"><?= $task_count ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>