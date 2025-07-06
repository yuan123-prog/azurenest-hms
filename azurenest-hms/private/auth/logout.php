<?php
session_start();
session_destroy();
header("Location: http://localhost/azurenest-hms/azurenest-hms/public/index.php");
exit();
