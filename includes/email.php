<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/email.php';


/**
 * Send an email through Brevo HTTPS API.
 *
 * This does NOT use SMTP.
 * It uses HTTPS port 443.
 */
function brevo_send_email(
    string $to,
    string $toName,
    string $subject,
    string $html,
    string $text
): void {

    global
        $BREVO_API_KEY,
        $SMTP_FROM_EMAIL,
        $SMTP_FROM_NAME;


    /*
     * Check configuration.
     */
    if (!email_configured()) {

        throw new RuntimeException(
            email_setup_message()
        );
    }


    /*
     * Brevo transactional email API.
     *
     * Official endpoint:
     * https://api.brevo.com/v3/smtp/email
     */
    $url = 'https://api.brevo.com/v3/smtp/email';


    /*
     * Request body.
     */
    $payload = [
        'sender' => [
            'name' => $SMTP_FROM_NAME,
            'email' => $SMTP_FROM_EMAIL
        ],

        'to' => [
            [
                'email' => $to,
                'name' => $toName
            ]
        ],

        'subject' => $subject,

        'htmlContent' => $html,

        'textContent' => $text
    ];


    /*
     * Encode JSON.
     */
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


    if ($json === false) {

        throw new RuntimeException(
            'Could not prepare email request.'
        );
    }


    /*
     * cURL is preferred because it handles
     * HTTPS/TLS correctly.
     */
    if (!function_exists('curl_init')) {

        throw new RuntimeException(
            'PHP cURL extension is not available on the server.'
        );
    }


    $curl = curl_init($url);


    if ($curl === false) {

        throw new RuntimeException(
            'Could not initialize HTTPS email connection.'
        );
    }


    try {

        curl_setopt_array(
            $curl,
            [
                CURLOPT_POST => true,

                CURLOPT_POSTFIELDS => $json,

                CURLOPT_HTTPHEADER => [
                    'accept: application/json',
                    'api-key: ' . $BREVO_API_KEY,
                    'content-type: application/json'
                ],

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_CONNECTTIMEOUT => 15,

                CURLOPT_TIMEOUT => 30,

                CURLOPT_SSL_VERIFYPEER => true,

                CURLOPT_SSL_VERIFYHOST => 2
            ]
        );


        /*
         * Execute request.
         */
        $response = curl_exec($curl);


        /*
         * cURL/network error.
         */
        if ($response === false) {

            $curlError = curl_error($curl);
            $curlErrno = curl_errno($curl);

            error_log(
                'BREVO API CONNECTION ERROR | Code=' .
                $curlErrno .
                ' | Error=' .
                $curlError
            );

            throw new RuntimeException(
                'Could not connect to the email service.'
            );
        }


        /*
         * HTTP response code.
         */
        $httpCode = (int)curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );


        /*
         * Decode Brevo response.
         */
        $decoded = json_decode(
            $response,
            true
        );


        /*
         * Success.
         *
         * Brevo normally returns HTTP 201.
         */
        if (
            $httpCode >= 200 &&
            $httpCode < 300
        ) {

            $messageId =
                is_array($decoded) &&
                isset($decoded['messageId'])
                    ? (string)$decoded['messageId']
                    : '';

            error_log(
                'BREVO EMAIL SENT | HTTP=' .
                $httpCode .
                ($messageId !== ''
                    ? ' | MessageID=' . $messageId
                    : '')
            );

            return;
        }


        /*
         * Extract safe error information.
         */
        $brevoMessage = '';

        if (
            is_array($decoded) &&
            isset($decoded['message'])
        ) {
            $brevoMessage =
                (string)$decoded['message'];
        }

        if ($brevoMessage === '') {
            $brevoMessage =
                trim($response);
        }


        /*
         * Never log the API key.
         */
        error_log(
            'BREVO API ERROR | HTTP=' .
            $httpCode .
            ' | Message=' .
            $brevoMessage
        );


        throw new RuntimeException(
            'Brevo API error ' .
            $httpCode .
            ': ' .
            $brevoMessage
        );


    } finally {

        curl_close($curl);
    }
}


/**
 * Send OTP email.
 */
function send_otp_email(
    string $to,
    string $name,
    string $otp
): void {

    global $OTP_EXPIRY_MINUTES;


    /*
     * Prevent HTML injection in recipient name.
     */
    $safeName = htmlspecialchars(
        $name,
        ENT_QUOTES,
        'UTF-8'
    );


    /*
     * OTP validity text.
     */
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
     * Plain text version.
     */
    $text =
        "Hello {$name},\n\n" .
        "Your Masha Allah Mobile verification code is: {$otp}\n\n" .
        "{$validityText}\n\n" .
        "If you did not create this account, ignore this email.";


    /*
     * Send through Brevo API.
     */
    brevo_send_email(
        $to,
        $name,
        'Verify your Masha Allah Mobile account',
        $html,
        $text
    );
}
