<?php
session_start();
session_destroy();
header("Location: /azurenest-hms/azurenest-hms/azurenest-hms/public/index.php");
exit();
?>