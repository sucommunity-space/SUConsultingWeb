<?php

header('Content-Type: application/json; charset=UTF-8');

function jsonResponse(bool $success, string $message, int $status = 200, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function envValue(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function cleanText(string $value): string
{
    return trim(strip_tags($value));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido.', 405);
}

$name = cleanText($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone = cleanText($_POST['phone'] ?? '');
$service = cleanText($_POST['service'] ?? '');
$message = cleanText($_POST['message'] ?? '');
$company = cleanText($_POST['company'] ?? '');
$turnstileToken = $_POST['cf-turnstile-response'] ?? '';

if ($company !== '') {
    jsonResponse(false, 'Solicitud no válida.', 400);
}

$errors = [];

if ($name === '' || mb_strlen($name) < 3) {
    $errors[] = 'El nombre es obligatorio.';
}
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El correo electrónico no es válido.';
}
if ($message === '' || mb_strlen($message) < 10) {
    $errors[] = 'El mensaje debe tener al menos 10 caracteres.';
}
if ($turnstileToken === '') {
    $errors[] = 'La verificación de seguridad es obligatoria.';
}

if (!empty($errors)) {
    jsonResponse(false, 'Hay campos inválidos.', 422, ['errors' => $errors]);
}

$turnstileSecret = envValue('TURNSTILE_SECRET_KEY', '');

if ($turnstileSecret === '') {
    jsonResponse(false, 'Falta configurar TURNSTILE_SECRET_KEY en el servidor.', 500);
}

$postFields = http_build_query([
    'secret' => $turnstileSecret,
    'response' => $turnstileToken,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
]);

$ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);

$verifyResponse = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($verifyResponse === false) {
    jsonResponse(false, 'No se pudo validar la verificación de seguridad.', 500, ['detail' => $curlError]);
}

$verifyData = json_decode($verifyResponse, true);

if (!is_array($verifyData) || empty($verifyData['success'])) {
    jsonResponse(false, 'La verificación de seguridad falló.', 422, ['turnstile' => $verifyData]);
}

$to = envValue('CONTACT_TO_EMAIL', 'contacto@suconsulting.pe');
$from = envValue('CONTACT_FROM_EMAIL', 'no-reply@suconsulting.pe');

$subject = 'Nuevo mensaje desde el formulario web - SUConsulting';
$body = "Nuevo mensaje recibido desde el sitio web.\n\n";
$body .= "Nombre: {$name}\n";
$body .= "Correo: {$email}\n";
$body .= "Teléfono: {$phone}\n";
$body .= "Servicio: {$service}\n";
$body .= "Mensaje:\n{$message}\n";

$headers = [];
$headers[] = 'From: SUConsulting Web <' . $from . '>';
$headers[] = 'Reply-To: ' . $email;
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

$sent = mail($to, $subject, $body, implode("\r\n", $headers));

if (!$sent) {
    jsonResponse(false, 'No se pudo enviar el mensaje. Inténtalo nuevamente.', 500);
}

jsonResponse(true, 'Tu mensaje fue enviado correctamente.');