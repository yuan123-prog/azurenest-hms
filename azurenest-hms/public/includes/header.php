<!DOCTYPE html>
<html>

<head>
    <title>AzureNest Hotel Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="dashboard-layout">
        <?php
        // Toast notification logic
        $toast = '';
        if (isset($_GET['success'])) {
            $toast = '<div class="toast toast-success" id="toast">' . htmlspecialchars($_GET['success']) . '</div>';
        } elseif (isset($_GET['error'])) {
            $toast = '<div class="toast toast-error" id="toast">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        if ($toast)
            echo $toast;
        ?>
        <script>
            // Simple toast show/hide
            const toast = document.getElementById('toast');
            if (toast) {
                setTimeout(() => { toast.classList.add('show'); }, 100);
                setTimeout(() => { toast.classList.remove('show'); }, 4000);
            }
        </script>