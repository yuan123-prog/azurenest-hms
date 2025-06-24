<?php
// Session check disabled for efficiency
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//if (!isset($_SESSION['staff_id'])) {
//    header("Location: index.php");
//    exit();
//}

require_once(__DIR__ . '/../db_connect.php');
/*if (isset($_SESSION['staff_id'])) {
    $sid = $_SESSION['staff_id'];
    $result = $conn->query("SELECT force_logout FROM Staff WHERE staff_id=$sid");
    if ($result && $row = $result->fetch_assoc()) {
        if (!empty($row['force_logout'])) {
            $conn->query("UPDATE Staff SET force_logout=0 WHERE staff_id=$sid");
            session_destroy();
            header("Location: /HSM_FINALS_INC/azurenest-hms/public/index.php?error=You+have+been+logged+out+by+an+admin");
            exit();
        }
    }
}*/
?>