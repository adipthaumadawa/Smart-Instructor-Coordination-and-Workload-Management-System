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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h4 class="mb-3"><i class="fas fa-key me-2"></i>Forgot Password</h4>
                        <p class="text-muted">This is a placeholder page for the academic project.</p>
                        
                        <div class="alert alert-info">
                            <strong>For demo purposes:</strong><br>
                            All test accounts use the password: <code>password123</code><br><br>
                            In a real system, this page would allow users to request a password reset link via email.
                        </div>
                        
                        <a href="login.php" class="btn btn-outline-primary w-100">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>