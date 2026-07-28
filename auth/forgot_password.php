<?php
/**
 * Forgot Password (Placeholder - For Academic Project)
 * In real system, this would send reset link via email
 */
session_start();
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/stitch-theme.css') ?>">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h4 class="mb-3"><i>key</i>Forgot Password</h4>
                        <p class="text-muted">This is a placeholder page for the academic project.</p>
                        
                        <div>
                            <strong>For demo purposes:</strong><br>
                            All test accounts use the password: <code>password123</code><br><br>
                            In a real system, this page would allow users to request a password reset link via email.
                        </div>
                        
                        <a href="login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>