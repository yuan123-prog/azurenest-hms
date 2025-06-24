<?php
session_start();
session_destroy();
header("Location: /HSM_FINALS_INC/azurenest-hms/public/index.php");
exit();
?>