<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
include "includes/header.php";
include "includes/sidebar.php";

// Get user role
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';
// Set timezone to your local timezone (e.g., Asia/Manila) BEFORE getting date/time
date_default_timezone_set('Asia/Manila');
// Get current date/time
$date = date('F j, Y');
$time = date('h:i A');

// Room status summary
$room_counts = [
    'Available' => 0,
    'Occupied' => 0,
    'Reserved' => 0,
    'Maintenance' => 0
];
$res = $conn->query("SELECT status, COUNT(*) as cnt FROM Rooms GROUP BY status");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $status = $row['status'];
        if (isset($room_counts[$status]))
            $room_counts[$status] = $row['cnt'];
    }
}
// Today's bookings
$today = date('Y-m-d');
$checkins = $conn->query("SELECT COUNT(*) as cnt FROM Bookings WHERE DATE(check_in) = '$today'")->fetch_assoc()['cnt'];
$checkouts = $conn->query("SELECT COUNT(*) as cnt FROM Bookings WHERE DATE(check_out) = '$today'")->fetch_assoc()['cnt'];
// Pending payments (handle missing Receipts table gracefully)
$pending_payments = 0;
$receipts_table_exists = $conn->query("SHOW TABLES LIKE 'Receipts'");
if ($receipts_table_exists && $receipts_table_exists->num_rows > 0) {
    $pending_payments_result = $conn->query("SELECT COUNT(*) as cnt FROM Receipts WHERE status='Unpaid'");
    $pending_payments = $pending_payments_result ? $pending_payments_result->fetch_assoc()['cnt'] : 0;
}
// Housekeeping pending
$pending_cleaning = $conn->query("SELECT COUNT(*) as cnt FROM Housekeeping WHERE status IN ('Dirty','Cleaning in Progress')");
$pending_cleaning = $pending_cleaning ? $pending_cleaning->fetch_assoc()['cnt'] : 0;
// Maintenance alerts
$maintenance_alerts = $conn->query("SELECT COUNT(*) as cnt FROM Housekeeping WHERE status='Needs Maintenance'");
$maintenance_alerts = $maintenance_alerts ? $maintenance_alerts->fetch_assoc()['cnt'] : 0;
// Guest messages (handle missing is_read column gracefully)
$unread_msgs = 0;
$col_check = $conn->query("SHOW COLUMNS FROM Communication LIKE 'is_read'");
if ($col_check && $col_check->num_rows > 0) {
    $unread_msgs_result = $conn->query("SELECT COUNT(*) as cnt FROM Communication WHERE is_read=0");
    $unread_msgs = $unread_msgs_result ? $unread_msgs_result->fetch_assoc()['cnt'] : 0;
}
// Recent activity
$recent = $conn->query("SELECT * FROM Audit_Log ORDER BY timestamp DESC LIMIT 5");
// System alerts (example: low inventory)
$low_inventory = $conn->query("SELECT COUNT(*) as cnt FROM Inventory WHERE quantity < 5");
$low_inventory = $low_inventory ? $low_inventory->fetch_assoc()['cnt'] : 0;
?>
<main class="dashboard-content">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <div>
            <h1 style="margin-bottom:4px;">Welcome to AzureNest Hotel Management System</h1>
            <div style="color:#555;font-size:1.1em;">Today: <strong><?= $date ?></strong> &nbsp; | &nbsp; Time:
                <strong><?= $time ?></strong>
            </div>
            <div style="color:#2563eb;font-weight:600;margin-top:4px;">Role: <?= htmlspecialchars($user_role) ?></div>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-bottom:32px;">
        <div class="widget-card"
            style="background:#f8fafc;padding:18px 20px;border-radius:10px;box-shadow:0 2px 8px #e0e7ef;">
            <div style="font-weight:600;">Total Rooms</div>
            <div style="margin:8px 0 0 0;">
                <span style="color:#059669;">Available: <?= $room_counts['Available'] ?></span> &nbsp;|
                <span style="color:#2563eb;">Occupied: <?= $room_counts['Occupied'] ?></span> &nbsp;|
                <span style="color:#f59e42;">Reserved: <?= $room_counts['Reserved'] ?></span> &nbsp;|
                <span style="color:#b91c1c;">Maintenance: <?= $room_counts['Maintenance'] ?></span>
            </div>
        </div>
        <div class="widget-card"
            style="background:#f8fafc;padding:18px 20px;border-radius:10px;box-shadow:0 2px 8px #e0e7ef;">
            <div style="font-weight:600;">Today's Bookings</div>
            <div style="margin-top:8px;">Check-ins: <span style="color:#2563eb;"><?= $checkins ?></span> &nbsp;|&nbsp;
                Check-outs: <span style="color:#059669;"><?= $checkouts ?></span></div>
        </div>
        <div class="widget-card"
            style="background:#f8fafc;padding:18px 20px;border-radius:10px;box-shadow:0 2px 8px #e0e7ef;">
            <div style="font-weight:600;">Housekeeping Status</div>
            <div style="margin-top:8px;color:#f59e42;">Pending Cleaning: <?= $pending_cleaning ?></div>
        </div>
        <div class="widget-card"
            style="background:#f8fafc;padding:18px 20px;border-radius:10px;box-shadow:0 2px 8px #e0e7ef;">
            <div style="font-weight:600;">Maintenance Alerts</div>
            <div style="margin-top:8px;color:#b91c1c;">Active Requests: <?= $maintenance_alerts ?></div>
        </div>
        <div class="widget-card"
            style="background:#f8fafc;padding:18px 20px;border-radius:10px;box-shadow:0 2px 8px #e0e7ef;">
            <div style="font-weight:600;">Guest Messages</div>
            <div style="margin-top:8px;color:#2563eb;">Unread: <?= $unread_msgs ?></div>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:32px;">
        <?php if ($user_role === 'Receptionist'): ?>
            <a href="rooms.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Room
                Management</a>
            <a href="bookings.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Bookings</a>
            <a href="guests.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Guest
                Profiles</a>
            <a href="services.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Services</a>
            <a href="communication.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Communication
                Center</a>
            <a href="logout.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#f87171;border-radius:8px;text-decoration:none;color:#fff;font-weight:600;">Logout</a>
        <?php elseif ($user_role === 'Maintenance'): ?>
            <a href="rooms.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Room
                Management</a>
            <a href="housekeeping.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Housekeeping</a>
            <a href="inventory.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Inventory</a>
            <a href="communication.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Communication
                Center</a>
            <a href="logout.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#f87171;border-radius:8px;text-decoration:none;color:#fff;font-weight:600;">Logout</a>
        <?php elseif ($user_role === 'Housekeeping'): ?>
            <a href="housekeeping.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Housekeeping</a>
            <a href="communication.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Communication
                Center</a>
            <a href="logout.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#f87171;border-radius:8px;text-decoration:none;color:#fff;font-weight:600;">Logout</a>
        <?php else: ?>
            <!-- Default: show all for admin/manager or unknown roles -->
            <a href="rooms.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Room
                Management</a>
            <a href="bookings.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Bookings</a>
            <a href="housekeeping.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Housekeeping</a>
            <a href="maintenance.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Maintenance</a>
            <a href="guests.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Guest
                Profiles</a>
            <a href="reports.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Reports</a>
            <a href="payments.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Payments</a>
            <a href="communication.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#e0e7ef;border-radius:8px;text-decoration:none;color:#222;font-weight:600;">Communication
                Center</a>
            <a href="logout.php" class="widget-card"
                style="text-align:center;padding:18px 0;background:#f87171;border-radius:8px;text-decoration:none;color:#fff;font-weight:600;">Logout</a>
        <?php endif; ?>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start;">
        <?php if ($user_role === 'Admin' || $user_role === 'Manager'): ?>
            <div style="flex:1 1 320px;min-width:320px;">
                <h3 style="margin-bottom:10px;">Recent Activity</h3>
                <ul
                    style="background:#fff;border-radius:8px;padding:16px 18px;box-shadow:0 2px 8px #e0e7ef;list-style:none;">
                    <?php while ($row = $recent && $recent->num_rows ? $recent->fetch_assoc() : []): ?>
                        <li style="margin-bottom:8px;font-size:0.98em;color:#444;">
                            <span style="color:#2563eb;font-weight:600;">[<?= htmlspecialchars($row['action']) ?>]</span>
                            <?= htmlspecialchars($row['details']) ?>
                            <span
                                style="color:#888;font-size:0.92em;float:right;"><?= date('M d, h:i A', strtotime($row['timestamp'])) ?></span>
                        </li>
                    <?php endwhile; ?>
                    <?php if (!$recent || !$recent->num_rows): ?>
                        <li style="color:#888;">No recent activity.</li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div style="flex:1 1 220px;min-width:220px;">
            <h3 style="margin-bottom:10px;">System Alerts</h3>
            <ul
                style="background:#fff;border-radius:8px;padding:16px 18px;box-shadow:0 2px 8px #e0e7ef;list-style:none;">
                <?php if ($low_inventory > 0): ?>
                    <li style="color:#b91c1c;">Low inventory items: <?= $low_inventory ?></li>
                <?php else: ?>
                    <li style="color:#059669;">No system alerts.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</main>
<?php include "includes/footer.php"; ?>