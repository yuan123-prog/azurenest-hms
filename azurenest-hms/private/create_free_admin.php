<?php
require_once __DIR__ . '/db_connect.php';
$username = '123';
$password = password_hash('123', PASSWORD_DEFAULT);
$role = 'Manager';
$name = 'Free Admin';
$sql = "INSERT INTO Staff (username, password, role, name) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password=VALUES(password), role=VALUES(role), name=VALUES(name)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $username, $password, $role, $name);
$stmt->execute();
echo "Free admin account created/updated.";
