<?php
/*
Suggested SQL logic for dynamic room status:

SELECT rooms.*,
       CASE 
           WHEN EXISTS (SELECT 1 FROM bookings WHERE room = rooms.room_number AND status = 'Checked-in') THEN 'Occupied'
           WHEN EXISTS (SELECT 1 FROM bookings WHERE room = rooms.room_number AND status = 'Reserved') THEN 'Reserved'
           ELSE rooms.status
       END AS display_status
FROM rooms;

-- Alternative approach:
SELECT rooms.*, 
       CASE 
           WHEN bookings.status = 'Checked-in' THEN 'Occupied'
           ELSE rooms.status
       END AS display_status
FROM rooms
LEFT JOIN bookings ON rooms.room_number = bookings.room
AND bookings.status = 'Checked-in';

-- Use display_status in Room Management instead of static room.status
*/

require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Manager');

// Room type to price mapping
$type_price_map = [
    'single' => 2000,
    'double' => 3500,
    'deluxe' => 5000,
    'suite' => 7500,
    'family' => 9000
];

// Fetch room types with price
$types = $conn->query("SELECT * FROM Room_Types");
$type_options = [];
$type_prices = [];
while ($row = $types->fetch_assoc()) {
    $type_options[$row['type_id']] = $row['type_name'];
    // Set price from map if type exists, else fallback to DB price
    $type_lower = strtolower($row['type_name']);
    $type_prices[$row['type_id']] = isset($type_price_map[$type_lower]) ? $type_price_map[$type_lower] : $row['price'];
}

// Error message variable
$room_error = '';

// Handle add room
if ($is_admin && isset($_POST['add_room'])) {
    $room_number = $_POST['room_number'];
    $type_id = $_POST['type_id'];
    $status = $_POST['status'];
    $type_name = isset($type_options[$type_id]) ? strtolower($type_options[$type_id]) : '';
    $price = isset($type_price_map[$type_name]) ? $type_price_map[$type_name] : 0;
    // Check for duplicate room_number
    $dup_stmt = $conn->prepare("SELECT COUNT(*) FROM Rooms WHERE room_number = ?");
    $dup_stmt->bind_param("s", $room_number);
    $dup_stmt->execute();
    $dup_stmt->bind_result($count);
    $dup_stmt->fetch();
    $dup_stmt->close();
    if ($count > 0) {
        $room_error = "Room number already exists. Please use a unique room number.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Rooms (room_number, type_id, status, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sisd", $room_number, $type_id, $status, $price);
        $stmt->execute();
        $stmt->close();
        // Audit log for room add
        $staff_id = $_SESSION['staff_id'];
        $details = "Room added: room_number=$room_number, type_id=$type_id, status=$status";
        $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'add_room', ?)");
        $log->bind_param("is", $staff_id, $details);
        $log->execute();
        $log->close();
        header("Location: rooms.php?success=Room+added");
        exit();
    }
}
// Handle delete room
if ($is_admin && isset($_GET['delete'])) {
    $room_id = intval($_GET['delete']);
    // Audit log before delete
    $staff_id = $_SESSION['staff_id'];
    $details = "Room deleted: room_id=$room_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'delete_room', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    $conn->query("DELETE FROM Rooms WHERE room_id=$room_id");
    header("Location: rooms.php?success=Room+deleted");
    exit();
}
// Handle edit room
if ($is_admin && isset($_POST['edit_room'])) {
    $room_id = $_POST['room_id'];
    $room_number = $_POST['room_number'];
    $type_id = $_POST['type_id'];
    $status = $_POST['status'];
    $type_name = isset($type_options[$type_id]) ? strtolower($type_options[$type_id]) : '';
    $price = isset($type_price_map[$type_name]) ? $type_price_map[$type_name] : 0;
    $stmt = $conn->prepare("UPDATE Rooms SET room_number=?, type_id=?, status=?, price=? WHERE room_id=?");
    $stmt->bind_param("sisdi", $room_number, $type_id, $status, $price, $room_id);
    $stmt->execute();
    $stmt->close();
    // Audit log for room edit
    $staff_id = $_SESSION['staff_id'];
    $details = "Room edited: room_id=$room_id, room_number=$room_number, type_id=$type_id, status=$status";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'edit_room', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    header("Location: rooms.php?success=Room+updated");
    exit();
}
// Search/filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$all_rooms = $conn->query("SELECT * FROM Rooms");
$filtered_rooms = [];
if ($all_rooms) {
    while ($room = $all_rooms->fetch_assoc()) {
        $room_id = intval($room['room_id']);
        // Calculate dynamic status
        $display_status = $room['status'];
        $active_booking = $conn->query("SELECT COUNT(*) as cnt FROM Bookings WHERE room_id=$room_id AND status='Checked-in'")->fetch_assoc()['cnt'];
        if ($active_booking > 0) {
            $display_status = 'Occupied';
        } else {
            $now = date('Y-m-d H:i:s');
            $future_reserved = $conn->query("SELECT COUNT(*) as cnt FROM Bookings WHERE room_id=$room_id AND status='Reserved' AND check_in > '$now'")->fetch_assoc()['cnt'];
            if ($future_reserved > 0) {
                $display_status = 'Reserved';
            } elseif ($display_status == 'Occupied') {
                $display_status = 'Available';
            }
        }
        $room['display_status'] = $display_status;
        $room['type_name'] = isset($type_options[$room['type_id']]) ? $type_options[$room['type_id']] : '';
        // If searching, filter here
        if ($search !== '') {
            $search_lc = mb_strtolower($search);
            $match = false;
            if (mb_stripos($room['room_number'], $search) !== false)
                $match = true;
            if (mb_stripos($room['type_name'], $search) !== false)
                $match = true;
            if (mb_stripos($display_status, $search) !== false)
                $match = true;
            if ($match)
                $filtered_rooms[] = $room;
        } else {
            $filtered_rooms[] = $room;
        }
    }
}
include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Room Management</h2>
    <?php if ($room_error): ?>
        <div id="room-error-modal"
            style="position:fixed;top:0;left:0;width:100vw;height:100vh;display:flex;align-items:center;justify-content:center;z-index:9999;background:rgba(0,0,0,0.25);">
            <div
                style="background:#fff3f3;border:2px solid #ffb3b3;padding:32px 48px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.15);color:#b30000;font-size:1.2em;text-align:center;min-width:320px;">
                <div style="margin-bottom:18px;">
                    <strong><?= htmlspecialchars($room_error) ?></strong>
                </div>
                <button onclick="document.getElementById('room-error-modal').style.display='none'"
                    style="background:#ffb3b3;color:#b30000;border:none;padding:8px 24px;border-radius:6px;font-size:1em;cursor:pointer;">OK</button>
            </div>
        </div>
    <?php endif; ?>
    <form method="get" style="margin-bottom:16px;">
        <input type="text" name="search" placeholder="Search by number, type, status"
            value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
        <?php if ($search): ?><a href="rooms.php">Clear</a><?php endif; ?>
    </form>
    <?php if ($is_admin): ?>
        <form method="post" style="margin-bottom:20px;">
            <input type="text" name="room_number" placeholder="Room Number" required>
            <select name="type_id" required>
                <option value="">Select Type</option>
                <?php foreach ($type_options as $id => $name): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="Available">Available</option>
                <option value="Occupied">Occupied</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Cleaning">Cleaning</option>
            </select>
            <!-- Price is set automatically, so no manual input -->
            <button type="submit" name="add_room">Add Room</button>
        </form>
    <?php endif; ?>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>ID</th>
            <th>Room Number</th>
            <th>Type</th>
            <th>Status</th>
            <th>Price</th>
            <?php if ($is_admin): ?>
                <th>Action</th><?php endif; ?>
        </tr>
        <?php while ($room = array_shift($filtered_rooms)): ?>
            <tr>
                <?php
                $display_status = $room['display_status'];
                $room_id = intval($room['room_id']);
                // 1. Check for Checked-in
                $active_booking = $conn->query("SELECT COUNT(*) as cnt FROM Bookings WHERE room_id=$room_id AND status='Checked-in'")->fetch_assoc()['cnt'];
                if ($active_booking > 0) {
                    $display_status = 'Occupied';
                } else {
                    // 2. Check for future Reserved
                    $now = date('Y-m-d H:i:s');
                    $future_reserved = $conn->query("SELECT COUNT(*) as cnt FROM Bookings WHERE room_id=$room_id AND status='Reserved' AND check_in > '$now'")->fetch_assoc()['cnt'];
                    if ($future_reserved > 0) {
                        $display_status = 'Reserved';
                    } elseif ($display_status == 'Occupied') {
                        // If no active booking but status is 'Occupied', show as 'Available'
                        $display_status = 'Available';
                    }
                }
                ?>
                <?php if ($is_admin && isset($_GET['edit']) && $_GET['edit'] == $room['room_id']): ?>
                    <form method="post" class="edit-form">
                        <td><?= $room['room_id'] ?><input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                        </td>
                        <td><input type="text" name="room_number" value="<?= htmlspecialchars($room['room_number']) ?>"
                                required></td>
                        <td><select name="type_id" required>
                                <?php foreach ($type_options as $id => $name): ?>
                                    <option value="<?= $id ?>" <?= $room['type_id'] == $id ? ' selected' : '' ?>>
                                        <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select></td>
                        <td><select name="status">
                                <option value="Available" <?= $room['status'] == 'Available' ? ' selected' : '' ?>>Available
                                </option>
                                <option value="Occupied" <?= $room['status'] == 'Occupied' ? ' selected' : '' ?>>Occupied
                                </option>
                                <option value="Maintenance" <?= $room['status'] == 'Maintenance' ? ' selected' : '' ?>>
                                    Maintenance</option>
                                <option value="Cleaning" <?= $room['status'] == 'Cleaning' ? ' selected' : '' ?>>Cleaning
                                </option>
                            </select></td>
                        <!-- Price is set automatically, display only -->
                        <td>
                            <?php
                            $type_name = isset($type_options[$room['type_id']]) ? strtolower($type_options[$room['type_id']]) : '';
                            $auto_price = isset($type_price_map[$type_name]) ? $type_price_map[$type_name] : 0;
                            ?>
                            ₱<?= number_format($auto_price, 2) ?>
                        </td>
                        <td>
                            <button type="submit" name="edit_room">Save</button>
                            <a href="rooms.php">Cancel</a>
                        </td>
                    </form>
                <?php else: ?>
                    <td><?= $room['room_id'] ?></td>
                    <td><?= htmlspecialchars($room['room_number']) ?></td>
                    <td><?= isset($type_options[$room['type_id']]) ? htmlspecialchars($type_options[$room['type_id']]) : 'N/A' ?>
                    </td>
                    <td><?= htmlspecialchars($display_status) ?></td>
                    <td>
                        <?php
                        $type_name = isset($type_options[$room['type_id']]) ? strtolower($type_options[$room['type_id']]) : '';
                        $auto_price = isset($type_price_map[$type_name]) ? $type_price_map[$type_name] : 0;
                        ?>
                        ₱<?= number_format($auto_price, 2) ?>
                    </td>
                    <?php if ($is_admin): ?>
                        <td>
                            <a href="rooms.php?edit=<?= $room['room_id'] ?>">Edit</a> |
                            <a href="rooms.php?delete=<?= $room['room_id'] ?>"
                                onclick="return confirm('Delete this room?')">Delete</a>
                        </td>
                    <?php endif; ?>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
<?php include "includes/footer.php"; ?>