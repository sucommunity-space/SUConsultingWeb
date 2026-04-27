<?php
declare(strict_types=1);

return [
    /*
     * Proveedor recomendado para envio real.
     * Opciones: resend, brevo, mail
     */
    'transport' => 'resend',

    /*
     * Correo destino que recibira los mensajes del formulario.
     */
    'to_email' => 'contacto@suconsulting.pe',

    /*
     * Remitente tecnico validado en tu proveedor.
     */
    'from_email' => 'no-reply@suconsulting.pe',

    /*
     * Nombre visible del remitente.
     */
    'from_name' => 'SUConsulting Web',

    /*
     * Prefijo del asunto que llegara a tu correo.
     */
    'subject_prefix' => 'Nuevo mensaje desde el formulario web - SUConsulting',

    /*
     * API key de Resend.
     */
    'resend_api_key' => '',

    /*
     * API key de Brevo.
     */
    'brevo_api_key' => '',
];
