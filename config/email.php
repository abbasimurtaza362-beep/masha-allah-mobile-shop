<?php
declare(strict_types=1);

/*
 * Brevo API configuration.
 *
 * IMPORTANT:
 * Do NOT put the API key directly in this file.
 * Add BREVO_API_KEY in Railway Variables.
 */

$BREVO_API_KEY = trim((string)(getenv('BREVO_API_KEY') ?: ''));

$SMTP_FROM_EMAIL = trim(
    (string)(getenv('SMTP_FROM_EMAIL') ?: '')
);

$SMTP_FROM_NAME = trim(
    (string)(
        getenv('SMTP_FROM_NAME')
        ?: 'Masha Allah Mobile & EasyPaisa Shop'
    )
);

$OTP_EXPIRY_MINUTES = 10;
$OTP_RESEND_SECONDS = 120;
$OTP_DAILY_MAX_REQUESTS = 10;
$OTP_DAILY_WINDOW_SECONDS = 86400;


/**
 * Check whether Brevo API is configured.
 */
function email_configured(): bool
{
    return email_configuration_issue() === null;
}


/**
 * Return configuration problem, if any.
 */
function email_configuration_issue(): ?string
{
    global
        $BREVO_API_KEY,
        $SMTP_FROM_EMAIL,
        $SMTP_FROM_NAME;

    if ($BREVO_API_KEY === '') {
        return 'Brevo API key is missing.';
    }

    if (
        !preg_match(
            '/^xkeysib-[A-Za-z0-9._-]+$/',
            $BREVO_API_KEY
        )
    ) {
        return 'Brevo API key format is invalid.';
    }

    if (
        filter_var(
            $SMTP_FROM_EMAIL,
            FILTER_VALIDATE_EMAIL
        ) === false
    ) {
        return 'A valid verified sender email is missing.';
    }

    if ($SMTP_FROM_NAME === '') {
        return 'Sender name is missing.';
    }

    return null;
}


/**
 * Return safe setup message.
 */
function email_setup_message(): string
{
    $issue = email_configuration_issue();

    return $issue
        ? 'Email setup is incomplete: ' . $issue
        : '';
}
