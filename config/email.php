<?php
declare(strict_types=1);

$SMTP_HOST = trim((string)(getenv('SMTP_HOST') ?: 'smtp-relay.brevo.com'));
$SMTP_PORT = (int)(getenv('SMTP_PORT') ?: 587);
$SMTP_USER = trim((string)(getenv('SMTP_USER') ?: ''));
$SMTP_PASS = (string)(getenv('SMTP_PASS') ?: '');
$SMTP_FROM_EMAIL = trim((string)(getenv('SMTP_FROM_EMAIL') ?: ''));
$SMTP_FROM_NAME = trim((string)(getenv('SMTP_FROM_NAME') ?: 'Masha Allah Mobile & EasyPaisa Shop'));
$OTP_EXPIRY_MINUTES = 10;
$OTP_RESEND_SECONDS = 120;
$OTP_DAILY_MAX_REQUESTS = 10;
$OTP_DAILY_WINDOW_SECONDS = 86400;

// An optional local XAMPP override can supply the shop's own SMTP settings when present.
$localEmailConfig = __DIR__ . '/email.local.php';
if (is_file($localEmailConfig)) {
    $local = require $localEmailConfig;
    if (is_array($local)) {
        foreach ($local as $key => $value) {
            switch ($key) {
                case 'SMTP_HOST': $SMTP_HOST = trim((string)$value); break;
                case 'SMTP_PORT': $SMTP_PORT = max(1, (int)$value); break;
                case 'SMTP_USER': $SMTP_USER = trim((string)$value); break;
                case 'SMTP_PASS': $SMTP_PASS = (string)$value; break;
                case 'SMTP_FROM_EMAIL': $SMTP_FROM_EMAIL = trim((string)$value); break;
                case 'SMTP_FROM_NAME': $SMTP_FROM_NAME = trim((string)$value); break;
                case 'OTP_EXPIRY_MINUTES': $OTP_EXPIRY_MINUTES = max(1, (int)$value); break;
                case 'OTP_RESEND_SECONDS': $OTP_RESEND_SECONDS = 120; break;
                case 'OTP_DAILY_MAX_REQUESTS': $OTP_DAILY_MAX_REQUESTS = 10; break;
                case 'OTP_DAILY_WINDOW_SECONDS': $OTP_DAILY_WINDOW_SECONDS = 86400; break;
            }
        }
    }
}

function email_configured(): bool
{
    return email_configuration_issue() === null;
}

function email_configuration_issue(): ?string
{
    global $SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASS, $SMTP_FROM_EMAIL;
    if ($SMTP_HOST === '' || $SMTP_PORT < 1) return 'SMTP server details are missing.';
    if (filter_var($SMTP_USER, FILTER_VALIDATE_EMAIL) === false) return 'A valid Brevo SMTP username is missing.';
    if ($SMTP_PASS === '' || preg_match('/paste|replace|your.*key|your.*secret/i', $SMTP_PASS)) return 'A valid Brevo SMTP key is missing.';
    if (filter_var($SMTP_FROM_EMAIL, FILTER_VALIDATE_EMAIL) === false || preg_match('/example\.com$/i', $SMTP_FROM_EMAIL)) return 'A verified sender email is missing.';
    return null;
}

function email_setup_message(): string
{
    $issue = email_configuration_issue();
    return $issue ? 'Email setup is incomplete: ' . $issue : '';
}
