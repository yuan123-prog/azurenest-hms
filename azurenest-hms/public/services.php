<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");

$role = $_SESSION['role'] ?? null;
$is_admin = ($role === 'Manager');
$can_view = ($role === 'Manager' || $role === 'Receptionist');
if (!$can_view) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

// Handle add service
if ($is_admin && isset($_POST['add_service'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $rate = $_POST['rate'];
    $stmt = $conn->prepare("INSERT INTO Services (name, description, rate) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $name, $description, $rate);
    $stmt->execute();
    $stmt->close();
    // Audit log for service add
    $staff_id = $_SESSION['staff_id'];
    $details = "Service added: name=$name, rate=$rate";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'add_service', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    header("Location: services.php?success=Service+added");
    exit();
}
// Handle delete service
if ($is_admin && isset($_GET['delete'])) {
    $service_id = intval($_GET['delete']);
    // Audit log before delete
    $staff_id = $_SESSION['staff_id'];
    $details = "Service deleted: service_id=$service_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'delete_service', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    $conn->query("DELETE FROM Services WHERE service_id=$service_id");
    header("Location: services.php?success=Service+deleted");
    exit();
}
// Fetch services
$services = $conn->query("SELECT * FROM Services");

include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Services Management</h2>
    <?php if ($is_admin): ?>
        <form method="post" style="margin-bottom:20px;">
            <input type="text" name="name" placeholder="Service Name" required>
            <input type="text" name="description" placeholder="Description">
            <input type="number" step="0.01" name="rate" placeholder="Rate" required>
            <button type="submit" name="add_service">Add Service</button>
        </form>
    <?php endif; ?>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Rate</th>
            <?php if ($is_admin): ?>
                <th>Action</th><?php endif; ?>
        </tr>
        <?php while ($service = $services->fetch_assoc()): ?>
            <tr>
                <td><?= $service['service_id'] ?></td>
                <td><?= htmlspecialchars($service['name']) ?></td>
                <td><?= htmlspecialchars($service['description']) ?></td>
                <td><?= htmlspecialchars($service['rate']) ?></td>
                <?php if ($is_admin): ?>
                    <td><a href="services.php?delete=<?= $service['service_id'] ?>"
                            onclick="return confirm('Delete this service?')">Delete</a></td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
<?php include "includes/footer.php"; ?>