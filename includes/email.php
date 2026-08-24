function smtp_expect($socket, array $codes): void {
    $response = smtp_read($socket);
    $code = (int)substr(trim($response), 0, 3);

    if (!in_array($code, $codes, true)) {
        $cleanResponse = trim(preg_replace('/\s+/', ' ', $response));

        error_log(
            'SMTP ERROR | Expected: ' . implode(',', $codes) .
            ' | Received: ' . $code .
            ' | Response: ' . $cleanResponse
        );

        throw new RuntimeException(
            'SMTP error ' . $code . ': ' . $cleanResponse
        );
    }
}
