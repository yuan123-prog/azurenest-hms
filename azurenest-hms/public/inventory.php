<?php
require_once("../private/auth/session_check.php");
require_once("../private/db_connect.php");

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Manager');

// Handle add item
if ($is_admin && isset($_POST['add_item'])) {
    $name = $_POST['name'];
    $quantity = $_POST['quantity'];
    $reorder_level = $_POST['reorder_level'];
    $supplier_id = $_POST['supplier_id'];
    $last_updated = $_POST['last_updated'];
    $stmt = $conn->prepare("INSERT INTO Inventory (name, quantity, reorder_level, supplier_id, last_updated) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siiis", $name, $quantity, $reorder_level, $supplier_id, $last_updated);
    $stmt->execute();
    $stmt->close();
    // Audit log for inventory add
    $staff_id = $_SESSION['staff_id'];
    $details = "Inventory added: name=$name, quantity=$quantity, supplier_id=$supplier_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'add_inventory', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    header("Location: inventory.php?success=Item+added");
    exit();
}
// Handle delete item
if ($is_admin && isset($_GET['delete'])) {
    $item_id = intval($_GET['delete']);
    // Audit log before delete
    $staff_id = $_SESSION['staff_id'];
    $details = "Inventory deleted: item_id=$item_id";
    $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'delete_inventory', ?)");
    $log->bind_param("is", $staff_id, $details);
    $log->execute();
    $log->close();
    $conn->query("DELETE FROM Inventory WHERE item_id=$item_id");
    header("Location: inventory.php?success=Item+deleted");
    exit();
}
// Fetch suppliers
$suppliers = $conn->query("SELECT * FROM Suppliers");
$supplier_options = [];
while ($row = $suppliers->fetch_assoc()) {
    $supplier_options[$row['supplier_id']] = $row['name'];
}
// Fetch inventory
$items = $conn->query("SELECT * FROM Inventory");

include "includes/header.php";
include "includes/sidebar.php";
?>
<main class="dashboard-content">
    <h2>Inventory Management</h2>
    <?php if ($is_admin): ?>
        <form method="post" style="margin-bottom:20px;">
            <input type="text" name="name" placeholder="Item Name" required>
            <input type="number" name="quantity" placeholder="Quantity" required>
            <input type="number" name="reorder_level" placeholder="Reorder Level" required>
            <select name="supplier_id" required>
                <option value="">Select Supplier</option>
                <?php if (empty($supplier_options)): ?>
                    <option value="1">Manila Linen & Laundry Supply Co.</option>
                    <option value="2">Cebu Hospitality Essentials Inc.</option>
                    <option value="3">Davao Foodservice Distributors</option>
                    <option value="4">Luzon Cleaning Solutions</option>
                    <option value="5">Visayas Hotel Equipment Trading</option>
                <?php else: ?>
                    <?php foreach ($supplier_options as $id => $name): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <input type="date" name="last_updated" required>
            <button type="submit" name="add_item">Add Item</button>
        </form>
    <?php endif; ?>
    <table border="1" cellpadding="8" style="width:100%;background:#fff;">
        <tr style="background:#f1f5f9;">
            <th>ID</th>
            <th>Name</th>
            <th>Quantity</th>
            <th>Reorder Level</th>
            <th>Supplier</th>
            <th>Last Updated</th>
            <?php if ($is_admin): ?>
                <th>Action</th><?php endif; ?>
        </tr>
        <?php while ($item = $items->fetch_assoc()): ?>
            <tr>
                <td><?= $item['item_id'] ?></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= htmlspecialchars($item['quantity']) ?></td>
                <td><?= htmlspecialchars($item['reorder_level']) ?></td>
                <td><?= isset($supplier_options[$item['supplier_id']]) ? htmlspecialchars($supplier_options[$item['supplier_id']]) : $item['supplier_id'] ?>
                </td>
                <td><?= htmlspecialchars($item['last_updated']) ?></td>
                <?php if ($is_admin): ?>
                    <td><a href="inventory.php?delete=<?= $item['item_id'] ?>"
                            onclick="return confirm('Delete this item?')">Delete</a></td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
    </table>
</main>
<?php include "includes/footer.php"; ?>