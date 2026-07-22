<?php
/**
 * Logout Handler
 */
require_once __DIR__ . '/../includes/auth.php';

// Call the logout function (it handles logging + session destroy + redirect)
logoutUser();
?>