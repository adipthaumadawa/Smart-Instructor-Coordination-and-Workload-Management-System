<?php
/** SMTP delivery for account recovery. Configure through server environment variables. */

function passwordResetMailReady(): bool
{
    return is_file(__DIR__ . '/../vendor/autoload.php')
        && getenv('SMTP_HOST') !== false && getenv('SMTP_HOST') !== ''
        && getenv('SMTP_USERNAME') !== false && getenv('SMTP_USERNAME') !== ''
        && getenv('SMTP_PASSWORD') !== false && getenv('SMTP_PASSWORD') !== ''
        && filter_var(getenv('SMTP_FROM'), FILTER_VALIDATE_EMAIL)
        && filter_var(getenv('PASSWORD_RESET_BASE_URL'), FILTER_VALIDATE_URL);
}

function sendPasswordResetLink(string $to, string $token): void
{
    if (!passwordResetMailReady()) {
        throw new RuntimeException('Password reset email is not configured.');
    }
    require_once __DIR__ . '/../vendor/autoload.php';

    // Fixed server-side origin prevents a forged HTTP Host header from changing reset links.
    $base = rtrim((string)getenv('PASSWORD_RESET_BASE_URL'), '/');
    $link = $base . '/auth/reset_password.php?token=' . rawurlencode($token);
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string)getenv('SMTP_HOST');
    $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
    $mail->SMTPAuth = true;
    $mail->Username = (string)getenv('SMTP_USERNAME');
    $mail->Password = (string)getenv('SMTP_PASSWORD');
    $mail->SMTPSecure = $mail->Port === 465
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom((string)getenv('SMTP_FROM'), (string)(getenv('SMTP_FROM_NAME') ?: 'Smart Instructor System'));
    $mail->addAddress($to);
    $mail->Subject = 'Reset your Smart Instructor password';
    $mail->Body = "We received a request to reset your password.\n\nOpen this link within 30 minutes:\n{$link}\n\nIf you did not request this, you can ignore this email.";
    $mail->send();
}
