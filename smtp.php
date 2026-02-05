<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/util.php';

function smtp_send_mail(string $to, string $subject, string $htmlBody, string $textBody=''): void {
    ensure_dirs();
    $host = SMTP_HOST; $port = SMTP_PORT; $enc = SMTP_ENCRYPTION;
    $fromEmail = SMTP_FROM_EMAIL; $fromName = SMTP_FROM_NAME;

    $scheme = 'tcp';
    if ($enc === 'tls') $scheme = 'tls'; // 465

    $socket = @stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) throw new Exception("SMTP connect failed: {$errstr} ({$errno})");

    $read = function() use ($socket) {
        $data = '';
        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) break;
            $data .= $line;
            if (preg_match('/^\d{3}\s/', $line)) break;
        }
        return $data;
    };
    $write = function(string $cmd) use ($socket) { fwrite($socket, $cmd . "\r\n"); };
    $expect = function(string $resp, array $codes) {
        $code = (int)substr($resp, 0, 3);
        if (!in_array($code, $codes, true)) throw new Exception("SMTP error: " . trim($resp));
    };

    $expect($read(), [220]);
    $write("EHLO rotransfer"); $expect($read(), [250]);

    if ($enc === 'starttls') {
        $write("STARTTLS"); $expect($read(), [220]);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception("STARTTLS failed");
        }
        $write("EHLO rotransfer"); $expect($read(), [250]);
    }

    if (SMTP_USER !== '') {
        $write("AUTH LOGIN"); $expect($read(), [334]);
        $write(base64_encode(SMTP_USER)); $expect($read(), [334]);
        $write(base64_encode(SMTP_PASS)); $expect($read(), [235]);
    }

    $write("MAIL FROM:<{$fromEmail}>"); $expect($read(), [250]);
    $write("RCPT TO:<{$to}>"); $expect($read(), [250, 251]);
    $write("DATA"); $expect($read(), [354]);

    $boundary = 'bnd_' . bin2hex(random_bytes(8));
    $date = date('r');
    $subj = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $headers = [
        "From: {$fromName} <{$fromEmail}>",
        "To: <{$to}>",
        "Subject: {$subj}",
        "Date: {$date}",
        "MIME-Version: 1.0",
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\""
    ];

    if ($textBody === '') $textBody = strip_tags($htmlBody);

    $msg  = implode("\r\n", $headers) . "\r\n\r\n";
    $msg .= "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$textBody}\r\n";
    $msg .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n";
    $msg .= "--{$boundary}--\r\n\r\n.\r\n";

    fwrite($socket, $msg); $expect($read(), [250]);
    $write("QUIT");
    fclose($socket);
}
