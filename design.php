<?php
require_once 'config.php';
if (!isLoggedIn()) redirect('index.php');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طراحی برج خنک‌کننده</title>
    <link rel="stylesheet" href="static/css/style.css">
    <script>
        const API_URL = 'api.php';
    </script>
    <script src="static/js/main.js" defer></script>
</head>
<body>
    <div class="container">
        <?php include 'templates/header.php'; ?>

        <div class="progress-timeline">
            <div class="progress-fill" id="progressFill"></div>
            <div class="progress-steps" id="progressSteps"></div>
        </div>

        <div class="content">
            <div id="step-content">
                <!-- مراحل اینجا لود می‌شن -->
            </div>
        </div>

        <footer>
            <p>© 2026 آرتیمان - سیستم طراحی هوشمند</p>
        </footer>
    </div>
</body>
</html>