<?php
/**
 * Email Configuration (PHPMailer Ready)
 * Smart Instructor Coordination and Workload Management System
 * 
 * To enable real emails:
 * 1. Install PHPMailer via Composer or download manually
 * 2. Uncomment and configure the settings below
 * 3. Use the sendEmail() function in functions.php
 */

// For now, we only log emails in email_logs table (already implemented)
// Real email sending can be added easily later.

define('MAIL_ENABLED', false); // Set to true after configuring PHPMailer
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM', 'noreply@ucsc.cmb.ac.lk');
define('MAIL_FROM_NAME', 'UCSC Smart Instructor System');

/**
 * Placeholder function - logs to database instead of sending real email
 * This keeps the system fully functional without PHPMailer dependency for the project
 */
function sendEmailNotification($to, $subject, $body, $userId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (to_email, subject, body, status, sent_at) 
            VALUES (?, ?, ?, 'sent', NOW())
        ");
        $stmt->execute([$to, $subject, $body]);
        
        // TODO: Integrate real PHPMailer here when needed
        // For academic project, logging is sufficient and demonstrates the concept
        
        return true;
    } catch (Exception $e) {
        // Log failure
        if (isset($pdo)) {
            $stmt = $pdo->prepare("
                INSERT INTO email_logs (to_email, subject, body, status, error_message) 
                VALUES (?, ?, ?, 'failed', ?)
            ");
            $stmt->execute([$to, $subject, $body, $e->getMessage()]);
        }
        return false;
    }
}
?>