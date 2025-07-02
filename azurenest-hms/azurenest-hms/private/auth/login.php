<?php
session_start();
require_once("../db_connect.php");

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM Staff WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true); // Prevent session fixation
    $_SESSION['staff_id'] = $user['staff_id'];
    $_SESSION['role'] = $user['role'];
    header("Location: ../../public/dashboard.php");
} else {
    header("Location: ../../public/index.php?error=Invalid+credentials");
}
?>