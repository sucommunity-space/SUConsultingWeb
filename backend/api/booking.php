<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../config/apps-script.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

try {
    $request = getRequestPayload();

    $fullName = normalizeText($request['fullName'] ?? '');
    $email = normalizeEmail($request['email'] ?? '');
    $phone = normalizeText($request['phone'] ?? '');
    $service = normalizeText($request['service'] ?? '');
    $date = trim((string) ($request['date'] ?? ''));
    $time = trim((string) ($request['time'] ?? ''));
    $notes = normalizeTextarea($request['notes'] ?? '');
    $privacy = normalizeText($request['privacy'] ?? '');
    $honeypot = normalizeText($request['website'] ?? '');
    $durationMinutes = isset($request['durationMinutes']) ? (int) $request['durationMinutes'] : (int) $config['default_duration_minutes'];

    if ($honeypot !== '') {
        respond(400, [
            'ok' => false,
            'message' => 'Solicitud invalida.',
        ]);
    }

    $errors = [];

    if ($fullName === '' || mb_strlen($fullName) < 3) {
        $errors['fullName'] = 'Ingresa tu nombre completo.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Ingresa un correo valido.';
    }

    if ($phone === '' || mb_strlen($phone) < 6) {
        $errors['phone'] = 'Ingresa un telefono valido.';
    }

    if ($service === '') {
        $errors['service'] = 'Indica el servicio que deseas.';
    }

    if ($privacy !== 'accepted') {
        $errors['privacy'] = 'Debes aceptar la politica de privacidad.';
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors['date'] = 'La fecha no tiene un formato valido.';
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        $errors['time'] = 'La hora no tiene un formato valido.';
    }

    if ($durationMinutes < 15 || $durationMinutes > 180) {
        $errors['durationMinutes'] = 'La duracion debe estar entre 15 y 180 minutos.';
    }

    if (mb_strlen($notes) > 2000) {
        $errors['notes'] = 'El mensaje es demasiado largo.';
    }

    if (!empty($errors)) {
        respond(422, [
            'ok' => false,
            'message' => 'Revisa los campos del formulario.',
            'errors' => $errors,
        ]);
    }

    $timezone = new DateTimeZone((string) $config['timezone']);
    $start = DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $timezone);

    if (!$start) {
        respond(422, [
            'ok' => false,
            'message' => 'No se pudo interpretar la fecha y hora.',
        ]);
    }

    $now = new DateTimeImmutable('now', $timezone);
    if ($start <= $now) {
        respond(422, [
            'ok' => false,
            'message' => 'La cita debe programarse en una fecha y hora futura.',
        ]);
    }

    $end = $start->modify(sprintf('+%d minutes', $durationMinutes));

    $payloadForScript = [
        'secret' => (string) $config['shared_secret'],
        'fullName' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'service' => $service,
        'notes' => $notes,
        'date' => $date,
        'time' => $time,
        'timezone' => (string) $config['timezone'],
        'durationMinutes' => $durationMinutes,
        'startIso' => $start->format(DATE_ATOM),
        'endIso' => $end->format(DATE_ATOM),
        'submittedAt' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
        'source' => 'suconsulting-web',
        'ip' => getClientIp(),
        'userAgent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ];

    $scriptResponse = postJson(
        (string) $config['apps_script_web_app_url'],
        $payloadForScript,
        (int) $config['request_timeout_seconds']
    );

    $upstream = decodeJsonObject($scriptResponse['body']);

    if ($scriptResponse['httpCode'] >= 400) {
        $upstreamMessage = is_array($upstream)
            ? (string) ($upstream['message'] ?? '')
            : extractUpstreamMessage($scriptResponse['body']);

        respond(502, [
            'ok' => false,
            'message' => $upstreamMessage !== '' ? $upstreamMessage : 'No se pudo conectar con el servicio de agenda.',
            'code' => is_array($upstream) ? ($upstream['code'] ?? 'UPSTREAM_HTTP_ERROR') : 'UPSTREAM_HTTP_ERROR',
            'upstreamStatus' => $scriptResponse['httpCode'],
        ]);
    }

    if (!is_array($upstream)) {
        respond(502, [
            'ok' => false,
            'message' => 'La respuesta del servicio de agenda no fue valida.',
            'upstreamStatus' => $scriptResponse['httpCode'],
        ]);
    }

    if (($upstream['ok'] ?? false) !== true) {
        $statusCode = ($upstream['code'] ?? '') === 'SLOT_TAKEN' ? 200 : 422;

        respond($statusCode, [
            'ok' => false,
            'message' => $upstream['message'] ?? 'No se pudo registrar la cita.',
            'code' => $upstream['code'] ?? 'BOOKING_ERROR',
            'details' => $upstream['details'] ?? null,
        ]);
    }

    respond(200, [
        'ok' => true,
        'message' => 'Tu cita fue registrada correctamente.',
        'eventId' => $upstream['eventId'] ?? null,
        'calendarId' => $upstream['calendarId'] ?? null,
        'startIso' => $upstream['startIso'] ?? $start->format(DATE_ATOM),
        'endIso' => $upstream['endIso'] ?? $end->format(DATE_ATOM),
    ]);
} catch (Throwable $e) {
    respond(500, [
        'ok' => false,
        'message' => 'Ocurrio un error interno en el servidor.',
        'error' => $e->getMessage(),
    ]);
}

function getRequestPayload(): array
{
    $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '[]', true);

        if (!is_array($data)) {
            throw new RuntimeException('JSON invalido.');
        }

        return $data;
    }

    return $_POST;
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

function postJson(string $url, array $payload, int $timeoutSeconds = 20): array
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        throw new RuntimeException('No se pudo serializar el payload JSON.');
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Error llamando a Apps Script: ' . $error);
    }

    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'httpCode' => $httpCode,
        'body' => $response,
    ];
}

function decodeJsonObject(string $body): ?array
{
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

function extractUpstreamMessage(string $body): string
{
    $body = trim($body);

    if ($body === '') {
        return '';
    }

    if (preg_match('/<title>(.*?)<\/title>/is', $body, $matches) === 1) {
        return trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    $plain = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    if ($plain === '') {
        return '';
    }

    return mb_substr($plain, 0, 280);
}

function respond(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
