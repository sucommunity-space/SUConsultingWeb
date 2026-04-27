<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../config/contact.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$name = normalizeText($_POST['name'] ?? '');
$email = normalizeEmail($_POST['email'] ?? '');
$phone = normalizeText($_POST['phone'] ?? '');
$service = normalizeText($_POST['service'] ?? '');
$subject = normalizeText($_POST['subject'] ?? '');
$message = normalizeTextarea($_POST['message'] ?? '');
$company = normalizeText($_POST['company'] ?? '');
$privacy = normalizeText($_POST['privacy'] ?? '');
$honeypot = normalizeText($_POST['website'] ?? '');

if ($honeypot !== '') {
    respond(400, [
        'success' => false,
        'message' => 'Solicitud invalida.',
    ]);
}

$errors = [];

if ($name === '' || mb_strlen($name) < 3) {
    $errors['name'] = 'Ingresa un nombre valido.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Ingresa un correo valido.';
}

if ($company === '' || mb_strlen($company) < 2) {
    $errors['company'] = 'Ingresa el nombre de la empresa.';
}

if ($phone === '' || mb_strlen($phone) < 6) {
    $errors['phone'] = 'Ingresa un telefono valido.';
}

if ($subject === '' || mb_strlen($subject) < 3) {
    $errors['subject'] = 'Ingresa un asunto valido.';
}

if ($message === '' || mb_strlen($message) < 10) {
    $errors['message'] = 'El mensaje debe tener al menos 10 caracteres.';
}

if ($privacy !== 'accepted') {
    $errors['privacy'] = 'Debes aceptar la politica de privacidad.';
}

if (!empty($errors)) {
    respond(422, [
        'success' => false,
        'message' => 'Revisa los campos del formulario.',
        'errors' => $errors,
    ]);
}

$transport = getConfigValue($config, 'transport', 'resend');
$to = getConfigValue($config, 'to_email', 'contacto@suconsulting.pe');
$from = getConfigValue($config, 'from_email', 'no-reply@suconsulting.pe');
$fromName = getConfigValue($config, 'from_name', 'SUConsulting Web');
$subjectPrefix = getConfigValue($config, 'subject_prefix', 'Nuevo mensaje desde el formulario web - SUConsulting');

$mailSubject = $subjectPrefix . ' | ' . $subject;
$body = [];
$body[] = 'Nuevo mensaje recibido desde el sitio web de SUConsulting';
$body[] = '';
$body[] = 'Nombre: ' . $name;
$body[] = 'Correo: ' . $email;
$body[] = 'Telefono: ' . $phone;
$body[] = 'Empresa: ' . $company;
$body[] = 'Servicio: ' . ($service !== '' ? $service : 'No especificado');
$body[] = 'Asunto: ' . $subject;
$body[] = '';
$body[] = 'Mensaje:';
$body[] = $message;
$body[] = '';
$body[] = 'Metadata tecnica:';
$body[] = 'IP: ' . (getClientIp() !== '' ? getClientIp() : 'N/A');
$body[] = 'User-Agent: ' . substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$body[] = 'Enviado: ' . gmdate(DATE_ATOM);

$mailText = implode("\n", $body);

try {
    $sent = sendContactEmail(
        $transport,
        $config,
        [
            'to' => $to,
            'from' => $from,
            'fromName' => $fromName,
            'replyTo' => $email,
            'replyToName' => $name,
            'subject' => $mailSubject,
            'text' => $mailText,
        ]
    );

    if (!$sent) {
        respond(500, [
            'success' => false,
            'message' => 'No se pudo enviar el mensaje. Revisa la configuracion del proveedor de correo.',
        ]);
    }
} catch (Throwable $e) {
    respond(500, [
        'success' => false,
        'message' => 'No se pudo enviar el mensaje.',
        'error' => $e->getMessage(),
    ]);
}

respond(200, [
    'success' => true,
    'message' => 'Tu mensaje fue enviado correctamente.',
]);

function sendContactEmail(string $transport, array $config, array $payload): bool
{
    return match (strtolower($transport)) {
        'resend' => sendWithResend($config, $payload),
        'brevo' => sendWithBrevo($config, $payload),
        'mail' => sendWithMail($payload),
        default => throw new RuntimeException('Transport de correo no soportado: ' . $transport),
    };
}

function sendWithResend(array $config, array $payload): bool
{
    $apiKey = getConfigValue($config, 'resend_api_key', '');

    if ($apiKey === '') {
        throw new RuntimeException('Falta resend_api_key en backend/config/contact.php');
    }

    $body = [
        'from' => formatAddress($payload['fromName'], $payload['from']),
        'to' => [$payload['to']],
        'subject' => $payload['subject'],
        'text' => $payload['text'],
        'reply_to' => [$payload['replyTo']],
    ];

    $response = postJsonRequest(
        'https://api.resend.com/emails',
        $body,
        [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ]
    );

    return $response['status'] >= 200 && $response['status'] < 300;
}

function sendWithBrevo(array $config, array $payload): bool
{
    $apiKey = getConfigValue($config, 'brevo_api_key', '');

    if ($apiKey === '') {
        throw new RuntimeException('Falta brevo_api_key en backend/config/contact.php');
    }

    $body = [
        'sender' => [
            'name' => $payload['fromName'],
            'email' => $payload['from'],
        ],
        'to' => [
            ['email' => $payload['to']],
        ],
        'replyTo' => [
            'email' => $payload['replyTo'],
            'name' => $payload['replyToName'],
        ],
        'subject' => $payload['subject'],
        'textContent' => $payload['text'],
    ];

    $response = postJsonRequest(
        'https://api.brevo.com/v3/smtp/email',
        $body,
        [
            'api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ]
    );

    return $response['status'] >= 200 && $response['status'] < 300;
}

function sendWithMail(array $payload): bool
{
    $headers = [];
    $headers[] = 'From: ' . formatAddress($payload['fromName'], $payload['from']);
    $headers[] = 'Reply-To: ' . $payload['replyTo'];
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

    return mail(
        $payload['to'],
        $payload['subject'],
        $payload['text'],
        implode("\r\n", $headers)
    );
}

function postJsonRequest(string $url, array $payload, array $headers): array
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        throw new RuntimeException('No se pudo serializar el payload JSON del correo.');
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Error enviando correo: ' . $error);
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $status,
        'body' => $response,
    ];
}

function formatAddress(string $name, string $email): string
{
    return trim($name) !== '' ? sprintf('%s <%s>', $name, $email) : $email;
}

function normalizeText(mixed $value): string
{
    $value = trim((string) $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return $value;
}

function normalizeTextarea(mixed $value): string
{
    $value = trim((string) $value);
    $value = preg_replace("/\r\n|\r/u", "\n", $value) ?? '';
    return $value;
}

function normalizeEmail(mixed $value): string
{
    return mb_strtolower(trim((string) $value));
}

function getConfigValue(array $config, string $key, string $default = ''): string
{
    $envKey = match ($key) {
        'transport' => 'CONTACT_MAIL_TRANSPORT',
        'to_email' => 'CONTACT_TO_EMAIL',
        'from_email' => 'CONTACT_FROM_EMAIL',
        'from_name' => 'CONTACT_FROM_NAME',
        'subject_prefix' => 'CONTACT_SUBJECT_PREFIX',
        'resend_api_key' => 'RESEND_API_KEY',
        'brevo_api_key' => 'BREVO_API_KEY',
        default => '',
    };

    if ($envKey !== '') {
        $envValue = getenv($envKey);

        if ($envValue !== false && $envValue !== '') {
            return trim((string) $envValue);
        }
    }

    $value = $config[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function getClientIp(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    foreach ($candidates as $candidate) {
        if ($candidate === '') {
            continue;
        }

        $parts = explode(',', $candidate);
        $ip = trim($parts[0]);

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }

    return '';
}

function respond(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
