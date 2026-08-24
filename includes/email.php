<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/email.php';


/**
 * Read complete SMTP response.
 */
function smtp_read($socket): string
{
    $data = '';

    while (!feof($socket)) {
        $line = fgets($socket, 515);

        if ($line === false) {
            break;
        }

        $data .= $line;

        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    return $data;
}


/**
 * Check SMTP response code.
 * Logs the real SMTP response to Railway without exposing passwords.
 */
function smtp_expect($socket, array $codes): void
{
    $response = smtp_read($socket);

    $trimmed = trim($response);

    $code = (int)substr($trimmed, 0, 3);

    if (!in_array($code, $codes, true)) {

        $cleanResponse = trim(
            preg_replace('/\s+/', ' ', $response)
        );

        error_log(
            'SMTP ERROR | Expected: ' .
            implode(',', $codes) .
            ' | Received: ' .
            $code .
            ' | Response: ' .
            $cleanResponse
        );

        throw new RuntimeException(
            'SMTP error ' .
            $code .
            ': ' .
            $cleanResponse
        );
    }
}


/**
 * Send SMTP command and validate response.
 */
function smtp_command(
    $socket,
    string $command,
    array $codes
): void {
    fwrite(
        $socket,
        $command . "\r\n"
    );

    smtp_expect(
        $socket,
        $codes
    );
}


/**
 * Send email through Brevo SMTP.
 */
function smtp_send_mail(
    string $to,
    string $subject,
    string $html,
    string $text
): void {

    global
        $SMTP_HOST,
        $SMTP_PORT,
        $SMTP_USER,
        $SMTP_PASS,
        $SMTP_FROM_EMAIL,
        $SMTP_FROM_NAME;


    /*
     * Check configuration first.
     */
    if (!email_configured()) {

        throw new RuntimeException(
            'Email service is not configured. ' .
            'Check Brevo SMTP credentials and verified sender.'
        );
    }


    /*
     * Create secure SSL context.
     */
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true
        ]
    ]);


    /*
     * Connect to Brevo SMTP.
     */
    $socket = @stream_socket_client(
        'tcp://' . $SMTP_HOST . ':' . $SMTP_PORT,
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT,
        $context
    );


    if (!$socket) {

        error_log(
            'SMTP CONNECTION ERROR | ' .
            'Host=' . $SMTP_HOST .
            ' | Port=' . $SMTP_PORT .
            ' | Error=' . $errstr .
            ' | Code=' . $errno
        );

        throw new RuntimeException(
            'Could not connect to the email server.'
        );
    }


    stream_set_timeout(
        $socket,
        20
    );


    try {

        /*
         * SMTP greeting.
         */
        smtp_expect(
            $socket,
            [220]
        );


        /*
         * Identify client.
         */
        smtp_command(
            $socket,
            'EHLO localhost',
            [250]
        );


        /*
         * Start TLS encryption.
         */
        smtp_command(
            $socket,
            'STARTTLS',
            [220]
        );


        /*
         * Enable TLS.
         */
        $crypto = stream_socket_enable_crypto(
            $socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );


        if ($crypto !== true) {

            error_log(
                'SMTP TLS ERROR | Could not establish TLS encryption.'
            );

            throw new RuntimeException(
                'Could not establish secure SMTP encryption.'
            );
        }


        /*
         * EHLO again after TLS.
         */
        smtp_command(
            $socket,
            'EHLO localhost',
            [250]
        );


        /*
         * Authenticate.
         */
        smtp_command(
            $socket,
            'AUTH LOGIN',
            [334]
        );


        /*
         * Username.
         */
        smtp_command(
            $socket,
            base64_encode($SMTP_USER),
            [334]
        );


        /*
         * Password / SMTP key.
         *
         * IMPORTANT:
         * The password itself is NEVER logged.
         */
        smtp_command(
            $socket,
            base64_encode($SMTP_PASS),
            [235]
        );


        /*
         * Sender.
         */
        smtp_command(
            $socket,
            'MAIL FROM:<' . $SMTP_FROM_EMAIL . '>',
            [250]
        );


        /*
         * Recipient.
         */
        smtp_command(
            $socket,
            'RCPT TO:<' . $to . '>',
            [250, 251]
        );


        /*
         * Start email content.
         */
        smtp_command(
            $socket,
            'DATA',
            [354]
        );


        /*
         * Build MIME email.
         */
        $boundary = bin2hex(
            random_bytes(12)
        );


        $headers = [];

        $headers[] =
            'From: ' .
            $SMTP_FROM_NAME .
            ' <' .
            $SMTP_FROM_EMAIL .
            '>';

        $headers[] =
            'To: <' .
            $to .
            '>';

        $encodedSubject =
            function_exists('mb_encode_mimeheader')
                ? mb_encode_mimeheader(
                    $subject,
                    'UTF-8'
                )
                : $subject;

        $headers[] =
            'Subject: ' .
            $encodedSubject;

        $headers[] =
            'MIME-Version: 1.0';

        $headers[] =
            'Content-Type: multipart/alternative; boundary="' .
            $boundary .
            '"';

        $headers[] =
            'Date: ' .
            date(DATE_RFC2822);


        $body =
            implode(
                "\r\n",
                $headers
            ) .
            "\r\n\r\n";


        /*
         * Plain text version.
         */
        $body .=
            '--' .
            $boundary .
            "\r\n" .
            'Content-Type: text/plain; charset=UTF-8' .
            "\r\n" .
            'Content-Transfer-Encoding: 8bit' .
            "\r\n\r\n" .
            $text .
            "\r\n\r\n";


        /*
         * HTML version.
         */
        $body .=
            '--' .
            $boundary .
            "\r\n" .
            'Content-Type: text/html; charset=UTF-8' .
            "\r\n" .
            'Content-Transfer-Encoding: 8bit' .
            "\r\n\r\n" .
            $html .
            "\r\n\r\n";


        /*
         * End MIME message.
         */
        $body .=
            '--' .
            $boundary .
            "--\r\n.";


        fwrite(
            $socket,
            $body . "\r\n"
        );


        /*
         * Brevo accepted the message.
         */
        smtp_expect(
            $socket,
            [250]
        );


        /*
         * Close SMTP session.
         */
        smtp_command(
            $socket,
            'QUIT',
            [221]
        );


    } finally {

        fclose($socket);
    }
}


/**
 * Generate and send OTP email.
 */
function send_otp_email(
    string $to,
    string $name,
    string $otp
): void {

    global $OTP_EXPIRY_MINUTES;


    $safeName = htmlspecialchars(
        $name,
        ENT_QUOTES,
        'UTF-8'
    );


    $validityText =
        $OTP_EXPIRY_MINUTES > 0
            ? 'This code expires in ' .
              $OTP_EXPIRY_MINUTES .
              ' minutes.'
            : 'This code remains valid until you request a new code.';


    /*
     * HTML email.
     */
    $html =
        '<div style="font-family:Arial,sans-serif;background:#f4f7f8;padding:32px">' .
            '<div style="max-width:560px;margin:auto;background:#fff;border-radius:18px;padding:34px">' .

                '<div style="font-size:12px;letter-spacing:2px;color:#b8842b;font-weight:700">' .
                    'MASHA ALLAH MOBILE' .
                '</div>' .

                '<h1 style="color:#071d2d;margin-bottom:8px">' .
                    'Verify your email' .
                '</h1>' .

                '<p style="color:#5d7180">' .
                    'Hello ' .
                    $safeName .
                    ', use the verification code below to activate your customer account.' .
                '</p>' .

                '<div style="font-size:34px;letter-spacing:10px;font-weight:800;color:#071d2d;text-align:center;padding:22px;background:#f4f7f8;border-radius:14px;margin:24px 0">' .
                    $otp .
                '</div>' .

                '<p style="color:#71828b">' .
                    $validityText .
                    ' If you did not create this account, you can ignore this email.' .
                '</p>' .

                '<p style="color:#71828b">' .
                    'Masha Allah Mobile &amp; EasyPaisa Shop<br>' .
                    'Quetta, Pakistan' .
                '</p>' .

            '</div>' .
        '</div>';


    /*
     * Plain text email.
     */
    $text =
        "Hello {$name},\n\n" .
        "Your Masha Allah Mobile verification code is: {$otp}\n\n" .
        "{$validityText}\n\n" .
        "If you did not create this account, ignore this email.";


    /*
     * Send.
     */
    smtp_send_mail(
        $to,
        'Verify your Masha Allah Mobile account',
        $html,
        $text
    );
}
