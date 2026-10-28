<?php

/**
 * Native SMTP Mailer - Surat Digital TVRI Jawa Timur
 * Standalone SMTP socket client for sending emails (TLS/STARTTLS & Auth)
 */
class Mailer
{
    public static function send(string $to, string $subject, string $bodyHtml, string $altBodyText = ''): bool
    {
        $host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $port = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
        $username = defined('SMTP_USER') ? SMTP_USER : 'medsostvrijatim@gmail.com';
        $password = defined('SMTP_PASS') ? SMTP_PASS : '';
        $fromEmail = defined('SMTP_FROM') ? SMTP_FROM : 'medsostvrijatim@gmail.com';
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Surat Digital TVRI';

        $logPath = __DIR__ . '/../logs/smtp.log';
        if (!file_exists(dirname($logPath))) {
            @mkdir(dirname($logPath), 0777, true);
        }

        // Fallback: If no password is set or connection fails, log OTP and attempt basic mail()
        if (empty($password)) {
            @file_put_contents($logPath, date('Y-m-d H:i:s') . " | Notice: SMTP_PASS is empty. Attempting fallback mail() to {$to}.\n", FILE_APPEND);
            $headers = "From: {$fromName} <{$fromEmail}>\r\n" .
                       "Reply-To: {$fromEmail}\r\n" .
                       "Content-Type: text/html; charset=UTF-8\r\n" .
                       "X-Mailer: PHP/" . phpversion();
            return @mail($to, $subject, $bodyHtml, $headers);
        }

        try {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
            if (!$socket) {
                @file_put_contents($logPath, date('Y-m-d H:i:s') . " | Connection Error: {$errstr} ({$errno})\n", FILE_APPEND);
                return false;
            }

            self::getResponse($socket);

            self::sendCommand($socket, "EHLO " . gethostname());
            self::getResponse($socket);

            // STARTTLS for Port 587
            if ($port === 587) {
                self::sendCommand($socket, "STARTTLS");
                $starttlsResp = self::getResponse($socket);
                if (!str_starts_with($starttlsResp, '220')) {
                    @file_put_contents($logPath, date('Y-m-d H:i:s') . " | STARTTLS Failed: {$starttlsResp}\n", FILE_APPEND);
                    fclose($socket);
                    return false;
                }

                $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
                if (!$crypto) {
                    @file_put_contents($logPath, date('Y-m-d H:i:s') . " | Crypto Enable TLS Failed\n", FILE_APPEND);
                    fclose($socket);
                    return false;
                }

                self::sendCommand($socket, "EHLO " . gethostname());
                self::getResponse($socket);
            }

            // AUTH LOGIN
            self::sendCommand($socket, "AUTH LOGIN");
            self::getResponse($socket);

            self::sendCommand($socket, base64_encode($username));
            self::getResponse($socket);

            self::sendCommand($socket, base64_encode($password));
            $authResp = self::getResponse($socket);
            if (!str_starts_with($authResp, '235')) {
                @file_put_contents($logPath, date('Y-m-d H:i:s') . " | SMTP Auth Failed for {$username}: {$authResp}\n", FILE_APPEND);
                fclose($socket);
                return false;
            }

            // MAIL FROM
            self::sendCommand($socket, "MAIL FROM: <{$fromEmail}>");
            self::getResponse($socket);

            // RCPT TO
            self::sendCommand($socket, "RCPT TO: <{$to}>");
            self::getResponse($socket);

            // DATA
            self::sendCommand($socket, "DATA");
            self::getResponse($socket);

            // Headers & Body
            $headers = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
            $headers .= "To: <{$to}>\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Date: " . date('r') . "\r\n";

            $message = $headers . "\r\n" . $bodyHtml . "\r\n.\r\n";
            self::sendCommand($socket, $message);
            $dataResp = self::getResponse($socket);

            self::sendCommand($socket, "QUIT");
            fclose($socket);

            $success = str_starts_with($dataResp, '250');
            @file_put_contents($logPath, date('Y-m-d H:i:s') . " | Mail sent to {$to} | Status: " . ($success ? 'SUCCESS' : 'FAILED: ' . $dataResp) . "\n", FILE_APPEND);
            return $success;

        } catch (Throwable $e) {
            @file_put_contents($logPath, date('Y-m-d H:i:s') . " | Mailer Exception: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }

    private static function sendCommand($socket, string $command): void
    {
        fputs($socket, $command . "\r\n");
    }

    private static function getResponse($socket): string
    {
        $response = "";
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") {
                break;
            }
        }
        return trim($response);
    }
}
