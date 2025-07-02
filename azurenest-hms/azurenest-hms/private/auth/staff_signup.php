<?php
session_start();
require_once("../db_connect.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager') {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $contact = trim($_POST['contact']);

    if ($password !== $confirm_password) {
        header("Location: ../../public/staff_signup.php?error=Passwords+do+not+match");
        exit();
    }

    // Check if username exists
    $stmt = $conn->prepare("SELECT staff_id FROM Staff WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header("Location: ../../public/staff_signup.php?error=Username+already+exists");
        exit();
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO Staff (name, username, password, role, contact) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $username, $hashed_password, $role, $contact);
    if ($stmt->execute()) {
        // Audit log for staff add
        $admin_id = $_SESSION['staff_id'];
        $details = "Staff added: username=$username, role=$role, name=$name";
        $log = $conn->prepare("INSERT INTO Audit_Log (staff_id, action, details) VALUES (?, 'add_staff', ?)");
        $log->bind_param("is", $admin_id, $details);
        $log->execute();
        $log->close();
        header("Location: ../../public/staff_signup.php?success=Staff+account+created+successfully");
    } else {
        header("Location: ../../public/staff_signup.php?error=Failed+to+create+account");
    }
    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: ../../public/staff_signup.php");
    exit();
}
