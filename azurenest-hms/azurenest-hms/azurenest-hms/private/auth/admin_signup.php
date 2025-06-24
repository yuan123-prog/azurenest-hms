<?php
require_once("../db_connect.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $contact = trim($_POST['contact']);

    if ($password !== $confirm_password) {
        header("Location: ../../public/admin_signup.php?error=Passwords+do+not+match");
        exit();
    }

    // Check if username exists
    $stmt = $conn->prepare("SELECT staff_id FROM Staff WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header("Location: ../../public/admin_signup.php?error=Username+already+exists");
        exit();
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'Manager';
    $stmt = $conn->prepare("INSERT INTO Staff (name, username, password, role, contact) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $username, $hashed_password, $role, $contact);
    if ($stmt->execute()) {
        header("Location: ../../public/admin_signup.php?success=Admin+account+created+successfully");
    } else {
        header("Location: ../../public/admin_signup.php?error=Failed+to+create+account");
    }
    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: ../../public/admin_signup.php");
    exit();
}
