<?php
namespace App\Core;

class Mailer {
    public static function send(string $to, string $subject, string $body, array $config): bool {
        $host = $config['host'] ?? '';
        $port = (int)($config['port'] ?? 25);
        $user = $config['user'] ?? '';
        $pass = $config['pass'] ?? '';
        $secure = $config['secure'] ?? '';
        $from = $config['from'] ?? $user;
        if ($host === '' || $from === '') {
            return false;
        }

        $remote = $host . ':' . $port;
        $ctx = stream_context_create();
        $fp = @stream_socket_client($remote, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            return false;
        }
        stream_set_timeout($fp, 10);

        $read = function() use ($fp) {
            $data = '';
            while (!feof($fp)) {
                $line = fgets($fp, 515);
                if ($line === false) break;
                $data .= $line;
                if (strlen($line) >= 4 && $line[3] === ' ') break;
            }
            return $data;
        };
        $write = function($cmd) use ($fp) {
            fwrite($fp, $cmd . "\r\n");
        };

        $read();
        $write('EHLO dcsolution.net');
        $read();

        if ($secure === 'tls') {
            $write('STARTTLS');
            $read();
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($fp);
                return false;
            }
            $write('EHLO dcsolution.net');
            $read();
        }

        if ($user !== '' && $pass !== '') {
            $write('AUTH LOGIN');
            $read();
            $write(base64_encode($user));
            $read();
            $write(base64_encode($pass));
            $read();
        }

        $write('MAIL FROM:<' . $from . '>');
        $read();
        $write('RCPT TO:<' . $to . '>');
        $read();
        $write('DATA');
        $read();

        $headers = [];
        $headers[] = 'From: ' . $from;
        $headers[] = 'To: ' . $to;
        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        $write($message . "\r\n.");
        $read();
        $write('QUIT');
        $read();
        fclose($fp);
        return true;
    }
}
