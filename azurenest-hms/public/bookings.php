<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");
$role = $_SESSION['role'] ?? null;
$is_admin = ($role === 'Manager');
$can_edit = ($is_admin || $role === 'Receptionist');
if (!$can_edit) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

// Handle add booking
if (($is_admin || $role === 'Receptionist') && isset($_POST['add_booking'])) {
    $guest_id = $_POST['guest_id'];
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $status = $_POST['status'];
    // Ensure correct format for DATETIME
    $check_in_dt = date('Y-m-d H:i:s', strtotime($check_in));
    $check_out_dt = date('Y-m-d H:i:s', strtotime($check_out));
    $stmt = $conn->prepare("INSERT INTO Bookings (guest_id, room_id, check_in, check_out, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $guest_id, $room_id, $check_in_dt, $check_out_dt, $status);
    $stmt->execute();
    $booking_id = $conn->insert_id;
    $stmt->close();
    // Update room status robustly
    if ($status === 'Checked-in') {
        $conn->query("UPDATE Rooms SET status='Occupied' WHERE room_id=" . intval($room_id));
    }
    if ($status === 'Checked-out' || $status === 'Cancelled') {
        // Only set to Available if no other checked-in bookings for this room
        $active = $conn->query("SELECT COUNT(*) as cnt FROM Bookings WHERE room_id=" . intval($room_id) . " AND status='Checked-in'")->fetch_assoc()['cnt'];
        if ($active == 0) {
            $conn->query("UPDATE Rooms SET status='Available' WHERE room_id=" . intval($room_id));
        }
    }
    // Get room price
    $room_price = 0;
    $room_res = $conn->query("SELECT price FROM Rooms WHERE room_id=" . intval($room_id));
    if ($room_res && $room_row = $room_res->fetch_assoc()) {
        $room_price = floatval($room_row['price']);
    }
    // Insert payment record
    $pay_stmt = $conn->prepare("INSERT INTO Payments (booking_id, amount, status, payment_date) VALUES (?, ?, 'Completed', NOW())");
    $pay_stmt->bind_param("id", $booking_id, $room_price);
    $pay_stmt->execute();
    $pay_stmt->close();
    // Audit log
    $staff_id = $_SESSION['staff_id'];
    $details = "Booking added: guest_id=$guest_id, room_id=$room_id, check_in=$check_in_dt, check_out=$check_out_dt, status=$status";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'add_booking', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    header("Location: bookings.php?success=Booking+added");
    exit();
}
// Handle delete booking
if (($is_admin || $role === 'Receptionist') && isset($_GET['delete'])) {
    $booking_id = intval($_GET['delete']);
    // Audit log before delete
    $staff_id = $_SESSION['staff_id'];
    $details = "Booking deleted: booking_id=$booking_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'delete_booking', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    $conn->query("DELETE FROM Bookings WHERE booking_id=$booking_id");
    header("Location: bookings.php?success=Booking+deleted");
    exit();
}
// Handle edit booking
if (($is_admin || $role === 'Receptionist') && isset($_POST['edit_booking'])) {
    $booking_id = $_POST['booking_id'];
    $guest_id = $_POST['guest_id'];
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $status = $_POST['status'];
    // Ensure correct format for DATETIME
    $check_in_dt = date('Y-m-d H:i:s', strtotime($check_in));
    $check_out_dt = date('Y-m-d H:i:s', strtotime($check_out));
    // Get previous room_id and status
    $prev = $conn->query("SELECT room_id, status FROM Bookings WHERE booking_id=" . intval($booking_id))->fetch_assoc();
    $prev_room_id = $prev['room_id'];
    $prev_status = $prev['status'];
    $stmt = $conn->prepare("UPDATE Bookings SET guest_id=?, room_id=?, check_in=?, check_out=?, status=? WHERE booking_id=?");
    $stmt->bind_param("iisssi", $guest_id, $room_id, $check_in_dt, $check_out_dt, $status, $booking_id);
    $stmt->execute();
    $stmt->close();
    // If room changed, check previous room
    if ($prev_room_id != $room_id) {
        // Only set to Available if no other checked-in bookings for previous room
        $active_prev = $conn->query("SELECT COUNT(*) as cnt FROM Bookings WHERE room_id=" . intval($prev_room_id) . " AND status='Checked-in'")->fetch_assoc()['cnt'];
        if ($active_prev == 0) {
            $conn->query("UPDATE Rooms SET status='Available' WHERE room_id=" . intval($prev_room_id));
        }
    }
    // Update new/current room status
    if ($status === 'Checked-in') {
        $conn->query("UPDATE Rooms SET status='Occupied' WHERE room_id=" . intval($room_id));
    }
    if ($status === 'Checked-out' || $status === 'Cancelled') {
        $active = $conn->query("SELECT COUNT(*) as cnt FROM Bookings WHERE room_id=" . intval($room_id) . " AND status='Checked-in'")->fetch_assoc()['cnt'];
        if ($active == 0) {
            $conn->query("UPDATE Rooms SET status='Available' WHERE room_id=" . intval($room_id));
        }
    }
    // Audit log
    $staff_id = $_SESSION['staff_id'];
    $details = "Booking edited: booking_id=$booking_id, guest_id=$guest_id, room_id=$room_id, check_in=$check_in_dt, check_out=$check_out_dt, status=$status";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'edit_booking', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    header("Location: bookings.php?success=Booking+updated");
    exit();
}
// Handle export to CSV
if (($is_admin || $role === 'Receptionist') && isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bookings_export_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Booking ID', 'Guest', 'Room', 'Check In', 'Check Out', 'Status']);
    $result = $conn->query("SELECT b.*, g.name AS guest_name, r.room_number FROM Bookings b LEFT JOIN Guest g ON b.guest_id = g.guest_id LEFT JOIN Rooms r ON b.room_id = r.room_id");
    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row['booking_id'],
            $row['guest_name'],
            $row['room_number'],
            $row['check_in'],
            $row['check_out'],
            $row['status']
        ]);
    }
    fclose($out);
    exit();
}
// Fetch guests
$guests = $conn->query("SELECT * FROM Guest");
$guest_options = [];
while ($row = $guests->fetch_assoc()) {
    $guest_options[$row['guest_id']] = $row['name'];
}
// Fetch rooms
$rooms = $conn->query("SELECT * FROM Rooms");
$room_options = [];
while ($row = $rooms->fetch_assoc()) {
    $room_options[$row['room_id']] = $row['room_number'];
}
// Search/filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
if ($search !== '') {
    $search_sql = $conn->real_escape_string($search);
    $where = "WHERE status LIKE '%$search_sql%'";
    foreach ($guest_options as $id => $name) {
        if (stripos($name, $search) !== false) {
            $where = $where ? $where . " OR guest_id=$id" : "WHERE guest_id=$id";
        }
    }
    foreach ($room_options as $id => $number) {
        if (stripos($number, $search) !== false) {
            $where = $where ? $where . " OR room_id=$id" : "WHERE room_id=$id";
        }
    }
}
$bookings = $conn->query("SELECT * FROM Bookings $where");
include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Booking Management</h2>
    <form method="get" style="margin-bottom:16px;display:inline-block;">
        <input type="text" name="search" placeholder="Search by guest, room, status"
            value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
        <?php if ($search): ?><a href="bookings.php">Clear</a><?php endif; ?>
    </form>
    <?php if ($can_edit): ?>
        <a href="bookings.php?export=csv" class="login-btn"
            style="margin-left:18px;display:inline-block;width:auto;padding:10px 24px;">Export to CSV</a>
    <?php endif; ?>
    <?php if ($can_edit): ?>
        <form method="post" style="margin-bottom:20px;">
            <label for="guest_id">Guest</label>
            <select name="guest_id" id="guest_id" required>
                <option value="">Select Guest</option>
                <?php foreach ($guest_options as $id => $name): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="room_id">Room</label>
            <select name="room_id" id="room_id" required>
                <option value="">Select Room</option>
                <?php foreach ($room_options as $id => $number): ?>
                    <option value="<?= $id ?>">Room <?= htmlspecialchars($number) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="calendar-group">
                <label for="check_in">Check-in Date & Time</label>
                <input type="datetime-local" name="check_in" id="check_in" required>
            </div>
            <div class="calendar-group">
                <label for="check_out">Check-out Date & Time</label>
                <input type="datetime-local" name="check_out" id="check_out" required>
            </div>
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="Reserved">Reserved</option>
                <option value="Checked-in">Checked-in</option>
                <option value="Checked-out">Checked-out</option>
                <option value="Cancelled">Cancelled</option>
            </select>
            <button type="submit" name="add_booking">Add Booking</button>
        </form>
    <?php endif; ?>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>ID</th>
            <th>Guest</th>
            <th>Room</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Status</th>
            <?php if ($can_edit): ?>
                <th>Action</th><?php endif; ?>
        </tr>
        <?php while ($booking = $bookings->fetch_assoc()): ?>
            <tr>
                <?php if ($can_edit && isset($_GET['edit']) && $_GET['edit'] == $booking['booking_id']): ?>
                    <form method="post" class="edit-form">
                        <td><?= $booking['booking_id'] ?><input type="hidden" name="booking_id"
                                value="<?= $booking['booking_id'] ?>"></td>
                        <td><label class="sr-only" for="edit_guest_id_<?= $booking['booking_id'] ?>">Guest</label><select
                                name="guest_id" id="edit_guest_id_<?= $booking['booking_id'] ?>" required>
                                <?php foreach ($guest_options as $id => $name): ?>
                                    <option value="<?= $id ?>" <?= $booking['guest_id'] == $id ? ' selected' : '' ?>>
                                        <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select></td>
                        <td><label class="sr-only" for="edit_room_id_<?= $booking['booking_id'] ?>">Room</label><select
                                name="room_id" id="edit_room_id_<?= $booking['booking_id'] ?>" required>
                                <?php foreach ($room_options as $id => $number): ?>
                                    <option value="<?= $id ?>" <?= $booking['room_id'] == $id ? ' selected' : '' ?>>Room
                                        <?= htmlspecialchars($number) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select></td>
                        <td>
                            <div class="calendar-group"><label class="sr-only"
                                    for="edit_check_in_<?= $booking['booking_id'] ?>">Check-in Date & Time</label><input
                                    type="datetime-local" name="check_in" id="edit_check_in_<?= $booking['booking_id'] ?>"
                                    value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($booking['check_in']))) ?>"
                                    required></div>
                        </td>
                        <td>
                            <div class="calendar-group"><label class="sr-only"
                                    for="edit_check_out_<?= $booking['booking_id'] ?>">Check-out Date & Time</label><input
                                    type="datetime-local" name="check_out" id="edit_check_out_<?= $booking['booking_id'] ?>"
                                    value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($booking['check_out']))) ?>"
                                    required></div>
                        </td>
                        <td><label class="sr-only" for="edit_status_<?= $booking['booking_id'] ?>">Status</label><select
                                name="status" id="edit_status_<?= $booking['booking_id'] ?>">
                                <option value="Reserved" <?= $booking['status'] == 'Reserved' ? ' selected' : '' ?>>Reserved
                                </option>
                                <option value="Checked-in" <?= $booking['status'] == 'Checked-in' ? ' selected' : '' ?>>Checked-in
                                </option>
                                <option value="Checked-out" <?= $booking['status'] == 'Checked-out' ? ' selected' : '' ?>>
                                    Checked-out</option>
                                <option value="Cancelled" <?= $booking['status'] == 'Cancelled' ? ' selected' : '' ?>>Cancelled
                                </option>
                            </select></td>
                        <td>
                            <button type="submit" name="edit_booking">Save</button>
                            <a href="bookings.php">Cancel</a>
                        </td>
                    </form>
                <?php else: ?>
                    <td><?= $booking['booking_id'] ?></td>
                    <td><?= isset($guest_options[$booking['guest_id']]) ? htmlspecialchars($guest_options[$booking['guest_id']]) : 'N/A' ?>
                    </td>
                    <td><?= isset($room_options[$booking['room_id']]) ? htmlspecialchars($room_options[$booking['room_id']]) : $booking['room_id'] ?>
                    </td>
                    <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($booking['check_in']))) ?><?= (strlen($booking['check_in']) > 10 && strpos($booking['check_in'], ':') !== false) ? '' : ' <span style="color:#d32f2f;font-size:0.9em;">(No time set)</span>' ?>
                    </td>
                    <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($booking['check_out']))) ?><?= (strlen($booking['check_out']) > 10 && strpos($booking['check_out'], ':') !== false) ? '' : ' <span style="color:#d32f2f;font-size:0.9em;">(No time set)</span>' ?>
                    </td>
                    <td><?= htmlspecialchars($booking['status']) ?></td>
                    <?php if ($can_edit): ?>
                        <td>
                            <a href="bookings.php?edit=<?= $booking['booking_id'] ?>">Edit</a> |
                            <a href="bookings.php?delete=<?= $booking['booking_id'] ?>"
                                onclick="return confirm('Delete this booking?')">Delete</a> |
                            <a href="request_service.php?booking_id=<?= $booking['booking_id'] ?>"
                                style="color:#2563eb;font-weight:500;">Request Service</a>
                        </td>
                    <?php endif; ?>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
<?php include "includes/footer.php"; ?>