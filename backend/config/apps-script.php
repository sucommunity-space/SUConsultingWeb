<?php
declare(strict_types=1);

return [
    /*
     * URL del deployment /exec de tu Apps Script.
     * Ejemplo:
     * https://script.google.com/macros/s/AKfycbxxxxxxxxxxxxxxxxxxxx/exec
     */
    'apps_script_web_app_url' => 'https://script.google.com/macros/s/AKfycbwB6XW7QjJUE8g7c9Cjz8pV0TwaZTDaf5Wcz3nPu3LEaH41Mr4nzYmy0lbS9v1cDQgG4A/exec',

    /*
     * Secreto compartido entre PHP y Apps Script.
     * Debe coincidir exactamente con BOOKING_SHARED_SECRET.
     */
    'shared_secret' => 'CAMBIA_ESTE_SECRETO_LARGO_Y_UNICO_2026',

    /*
     * Zona horaria principal del negocio.
     */
    'timezone' => 'America/Lima',

    /*
     * Duracion por defecto de la cita en minutos.
     */
    'default_duration_minutes' => 60,

    /*
     * Timeout maximo para esperar respuesta de Apps Script.
     */
    'request_timeout_seconds' => 20,
];
