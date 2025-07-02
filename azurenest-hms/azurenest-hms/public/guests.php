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

// At the top, before <main>
$show_duplicate_modal = false;
if (($is_admin || $role === 'Receptionist') && isset($_POST['add_guest'])) {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $preferences = trim($_POST['preferences']);
    // Check for duplicate guest (by name and email)
    $dup_stmt = $conn->prepare("SELECT guest_id FROM Guest WHERE name = ? AND email = ?");
    $dup_stmt->bind_param("ss", $name, $email);
    $dup_stmt->execute();
    $dup_stmt->store_result();
    if ($dup_stmt->num_rows > 0) {
        $dup_stmt->close();
        $show_duplicate_modal = true;
    } else {
        $dup_stmt->close();
        $stmt = $conn->prepare("INSERT INTO Guest (name, contact, email, preferences) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $contact, $email, $preferences);
        $stmt->execute();
        $stmt->close();
        // Audit log for guest add
        $staff_id = $_SESSION['staff_id'];
        $details = "Guest added: name=$name, email=$email, contact=$contact";
        $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'add_guest', ?)");
        $log->bind_param("is", $staff_id, $details);
        $log->execute();
        $log->close();
        header("Location: guests.php?success=Guest+added");
        exit();
    }
}
// Handle delete guest
if (($is_admin || $role === 'Receptionist') && isset($_GET['delete'])) {
    $guest_id = intval($_GET['delete']);
    // Audit log before delete
    $staff_id = $_SESSION['staff_id'];
    $details = "Guest deleted: guest_id=$guest_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'delete_guest', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    $conn->query("DELETE FROM Guest WHERE guest_id=$guest_id");
    header("Location: guests.php?success=Guest+deleted");
    exit();
}
// Handle edit guest
if (($is_admin || $role === 'Receptionist') && isset($_POST['edit_guest'])) {
    $guest_id = $_POST['guest_id'];
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $preferences = trim($_POST['preferences']);
    // Prevent duplicate (except for this guest)
    $dup_stmt = $conn->prepare("SELECT guest_id FROM Guest WHERE name = ? AND email = ? AND guest_id != ?");
    $dup_stmt->bind_param("ssi", $name, $email, $guest_id);
    $dup_stmt->execute();
    $dup_stmt->store_result();
    if ($dup_stmt->num_rows > 0) {
        $dup_stmt->close();
        $show_duplicate_modal = true;
    } else {
        $dup_stmt->close();
        $stmt = $conn->prepare("UPDATE Guest SET name=?, contact=?, email=?, preferences=? WHERE guest_id=?");
        $stmt->bind_param("ssssi", $name, $contact, $email, $preferences, $guest_id);
        $stmt->execute();
        $stmt->close();
        // Audit log for guest edit
        $staff_id = $_SESSION['staff_id'];
        $details = "Guest edited: guest_id=$guest_id, name=$name, email=$email, contact=$contact";
        $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'edit_guest', ?)");
        $log->bind_param("is", $staff_id, $details);
        $log->execute();
        $log->close();
        header("Location: guests.php?success=Guest+updated");
        exit();
    }
}
// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$total_guests = $conn->query("SELECT COUNT(*) as cnt FROM Guest")->fetch_assoc()['cnt'];
$total_pages = ceil($total_guests / $per_page);
// Fetch guests with limit
$guests = $conn->query("SELECT * FROM Guest LIMIT $offset, $per_page");

include "includes/header.php";
include "includes/sidebar.php";
?>
<?php if (isset($show_duplicate_modal) && $show_duplicate_modal): ?>
    <div id="guest-error-modal"
        style="position:fixed;top:0;left:0;width:100vw;height:100vh;display:flex;align-items:center;justify-content:center;z-index:9999;background:rgba(0,0,0,0.25);">
        <div
            style="background:#fff3f3;border:2px solid #ffb3b3;padding:32px 48px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.15);color:#b30000;font-size:1.2em;text-align:center;min-width:320px;">
            <div style="margin-bottom:18px;">
                <strong>A guest with this name and email already exists.</strong>
            </div>
            <button onclick="document.getElementById('guest-error-modal').style.display='none'"
                style="background:#ffb3b3;color:#b30000;border:none;padding:8px 24px;border-radius:6px;font-size:1em;cursor:pointer;">OK</button>
        </div>
    </div>
<?php endif; ?>
<main class="dashboard-content">
    <h2>Guest Profile Management</h2>
    <?php if ($can_edit): ?>
        <form method="post" style="margin-bottom:20px;">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="contact" placeholder="Contact" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="preferences" placeholder="Preferences">
            <button type="submit" name="add_guest">Add Guest</button>
        </form>
    <?php endif; ?>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>ID</th>
            <th>Name</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Preferences</th>
            <?php if ($can_edit): ?>
                <th>Action</th><?php endif; ?>
        </tr>
        <?php while ($guest = $guests->fetch_assoc()): ?>
            <tr>
                <?php if ($can_edit && isset($_GET['edit']) && $_GET['edit'] == $guest['guest_id']): ?>
                    <form method="post">
                        <td><?= $guest['guest_id'] ?><input type="hidden" name="guest_id" value="<?= $guest['guest_id'] ?>">
                        </td>
                        <td><input type="text" name="name" value="<?= htmlspecialchars($guest['name']) ?>" required></td>
                        <td><input type="text" name="contact" value="<?= htmlspecialchars($guest['contact']) ?>" required></td>
                        <td><input type="email" name="email" value="<?= htmlspecialchars($guest['email']) ?>" required></td>
                        <td><input type="text" name="preferences" value="<?= htmlspecialchars($guest['preferences']) ?>"></td>
                        <td>
                            <button type="submit" name="edit_guest">Save</button>
                            <a href="guests.php">Cancel</a>
                        </td>
                    </form>
                <?php else: ?>
                    <td><?= $guest['guest_id'] ?></td>
                    <td><?= htmlspecialchars($guest['name']) ?></td>
                    <td><?= htmlspecialchars($guest['contact']) ?></td>
                    <td><?= htmlspecialchars($guest['email']) ?></td>
                    <td><?= htmlspecialchars($guest['preferences']) ?></td>
                    <?php if ($can_edit): ?>
                        <td>
                            <a href="guests.php?edit=<?= $guest['guest_id'] ?>">Edit</a> |
                            <a href="guests.php?delete=<?= $guest['guest_id'] ?>"
                                onclick="return confirm('Delete this guest?')">Delete</a>
                        </td>
                    <?php endif; ?>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
    <?php if ($total_pages > 1): ?>
        <div style="text-align:center;margin-top:18px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="guests.php?page=<?= $i ?>"
                    style="margin:0 6px;<?= $i == $page ? 'font-weight:bold;color:#2563eb;text-decoration:underline;' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</main>
<?php include "includes/footer.php"; ?>