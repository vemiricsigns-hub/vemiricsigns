<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$logPath = __DIR__ . '/logs/contact-form.log';
$logDir = dirname($logPath);

if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

function logContactError(string $message): void
{
    global $logPath;

    $timestamp = date('Y-m-d H:i:s');
    $entry = sprintf("[%s] %s\n", $timestamp, $message);
    error_log($entry, 3, $logPath);
}

function getEnvValue(string $name, string $default = ''): string
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        $value = $_ENV[$name] ?? '';
    }

    return $value !== false ? (string) $value : $default;
}

function sanitizeText(?string $value): string
{
    return trim(strip_tags((string) ($value ?? '')));
}

function verifyRecaptcha(string $responseToken, string $remoteIp): bool
{
    $secret = getEnvValue('RECAPTCHA_SECRET_KEY');
    if ($secret === '' || $responseToken === '') {
        return true;
    }

    $verificationUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $payload = http_build_query([
        'secret' => $secret,
        'response' => $responseToken,
        'remoteip' => $remoteIp,
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 8,
        ],
    ]);

    $result = @file_get_contents($verificationUrl, false, $context);
    if ($result === false) {
        return false;
    }

    $data = json_decode($result, true);
    return isset($data['success']) && $data['success'] === true;
}

function sendSmtpEmail(string $recipientEmail, string $subject, string $body, array $headers): bool
{
    $smtpHost = getEnvValue('SMTP_HOST');
    $smtpUser = getEnvValue('SMTP_USER');
    $smtpPass = getEnvValue('SMTP_PASS');
    $smtpPort = (int) getEnvValue('SMTP_PORT', '587');
    $smtpEncryption = strtolower(getEnvValue('SMTP_ENCRYPTION', 'tls'));
    $timeout = 15;

    if ($smtpHost === '') {
        return false;
    }

    $transport = $smtpEncryption === 'ssl' ? 'ssl://' : '';
    $port = $smtpPort ?: ($smtpEncryption === 'ssl' ? 465 : 587);
    $remoteSocket = sprintf('%s%s:%d', $transport, $smtpHost, $port);

    $streamFlags = STREAM_CLIENT_CONNECT;
    if (defined('STREAM_CLIENT_PREFER_TCP')) {
        $streamFlags |= STREAM_CLIENT_PREFER_TCP;
    }
    $socket = @stream_socket_client($remoteSocket, $errno, $errstr, $timeout, $streamFlags);
    if ($socket === false) {
        logContactError("SMTP connection failed: {$errno} {$errstr}");
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $hostname = gethostname() ?: php_uname('n');
    if (!smtpCommand($socket, null, [220])) {
        fclose($socket);
        return false;
    }
    if (!smtpCommand($socket, "EHLO {$hostname}", [250])) {
        fclose($socket);
        return false;
    }

    if ($smtpEncryption === 'tls') {
        if (!smtpCommand($socket, 'STARTTLS', [220])) {
            fclose($socket);
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            logContactError('SMTP STARTTLS failed.');
            fclose($socket);
            return false;
        }
        if (!smtpCommand($socket, "EHLO {$hostname}", [250])) {
            fclose($socket);
            return false;
        }
    }

    if ($smtpUser !== '') {
        if (!smtpAuthenticate($socket, $smtpUser, $smtpPass)) {
            fclose($socket);
            return false;
        }
    }

    $fromEmail = getEnvValue('CONTACT_FROM_EMAIL', 'noreply@vemiricsigns.ae');
    if (!smtpCommand($socket, "MAIL FROM:<{$fromEmail}>", [250])) {
        fclose($socket);
        return false;
    }
    if (!smtpCommand($socket, "RCPT TO:<{$recipientEmail}>", [250, 251])) {
        fclose($socket);
        return false;
    }
    if (!smtpCommand($socket, 'DATA', [354])) {
        fclose($socket);
        return false;
    }

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n";
    fwrite($socket, $message);
    if (!smtpCommand($socket, null, [250])) {
        fclose($socket);
        return false;
    }
    smtpCommand($socket, 'QUIT', [221]);
    fclose($socket);
    return true;
}

function smtpCommand($socket, ?string $command, array $expectedCodes): bool
{
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }

    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    if ($response === '') {
        logContactError('SMTP response was empty.');
        return false;
    }

    $code = (int) substr($response, 0, 3);
    return in_array($code, $expectedCodes, true);
}

function smtpAuthenticate($socket, string $username, string $password): bool
{
    if (!smtpCommand($socket, 'AUTH LOGIN', [334])) {
        logContactError('SMTP auth challenge failed.');
        return false;
    }
    if (!smtpCommand($socket, base64_encode($username), [334])) {
        logContactError('SMTP username rejected.');
        return false;
    }
    if (!smtpCommand($socket, base64_encode($password), [235])) {
        logContactError('SMTP password rejected.');
        return false;
    }
    return true;
}

function sendQuoteEmail(array $data): bool
{
    $recipientEmail = getEnvValue('CONTACT_RECIPIENT_EMAIL', 'info@vemiricsigns.ae');
    $fromEmail = getEnvValue('CONTACT_FROM_EMAIL', 'noreply@vemiricsigns.ae');
    $fromName = getEnvValue('CONTACT_FROM_NAME', 'VEMIRIC SIGNS Website');
    $subject = 'New Quote Request from VEMIRIC SIGNS Website';
    $currentDateTime = date('Y-m-d H:i:s');
    $visitorIp = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    $body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Quote Request</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827;">
  <h2>New Quote Request Received</h2>
  <p><strong>Full Name:</strong><br>{$data['full_name']}</p>
  <p><strong>Company:</strong><br>{$data['company']}</p>
  <p><strong>Email:</strong><br>{$data['email']}</p>
  <p><strong>Phone / WhatsApp:</strong><br>{$data['phone']}</p>
  <p><strong>Selected Service:</strong><br>{$data['service']}</p>
  <p><strong>Project Description:</strong><br>{$data['project_description']}</p>
  <p><strong>Submitted On:</strong><br>{$currentDateTime}</p>
  <p><strong>Visitor IP:</strong><br>{$visitorIp}</p>
</body>
</html>
HTML;

    $headers = [];
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $data['email'];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'X-Mailer: PHP/' . PHP_VERSION;

    $smtpHost = getEnvValue('SMTP_HOST');
    if ($smtpHost !== '') {
        return sendSmtpEmail($recipientEmail, $subject, $body, array_merge(['Subject: ' . $subject], $headers));
    }

    return mail($recipientEmail, $subject, $body, implode("\r\n", $headers));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
    exit;
}

$postData = $_POST;

$honeypot = sanitizeText($postData['website'] ?? $postData['url'] ?? $postData['company_url'] ?? '');
if ($honeypot !== '') {
    logContactError('Spam attempt blocked: honeypot field filled.');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Your request could not be processed.',
    ]);
    exit;
}

$recaptchaToken = sanitizeText($postData['g-recaptcha-response'] ?? '');
if ($recaptchaToken !== '' && !verifyRecaptcha($recaptchaToken, $_SERVER['REMOTE_ADDR'] ?? '')) {
    logContactError('Spam attempt blocked: reCAPTCHA verification failed.');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Your request could not be verified as human.',
    ]);
    exit;
}

$fullName = sanitizeText($postData['name'] ?? $postData['full_name'] ?? '');
$company = sanitizeText($postData['company'] ?? '');
$email = sanitizeText($postData['email'] ?? '');
$phone = sanitizeText($postData['phone'] ?? '');
$service = sanitizeText($postData['service'] ?? '');
$projectDescription = sanitizeText($postData['details'] ?? $postData['project_description'] ?? '');

$errors = [];
if ($fullName === '') {
    $errors[] = 'Full Name is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid Email is required.';
}
if ($phone === '') {
    $errors[] = 'Phone / WhatsApp is required.';
}
if ($service === '') {
    $errors[] = 'Service is required.';
}
if ($projectDescription === '') {
    $errors[] = 'Project Description is required.';
}

if ($errors !== []) {
    logContactError('Validation failed: ' . implode(' ', $errors));
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please complete all required fields correctly.',
        'errors' => $errors,
    ]);
    exit;
}

$submissionData = [
    'full_name' => $fullName,
    'company' => $company,
    'email' => $email,
    'phone' => $phone,
    'service' => $service,
    'project_description' => $projectDescription,
];

$mailSent = sendQuoteEmail($submissionData);

if ($mailSent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your enquiry has been submitted successfully. Our team will contact you shortly.',
    ]);
    exit;
}

logContactError('Email delivery failed for: ' . $email);
http_response_code(500);
echo json_encode([
    'success' => false,
    'message' => 'Sorry, we could not send your enquiry right now. Please try again later.',
]);
