import type { BookingConfig } from '../types/booking';

export const booking: BookingConfig = {
    title: 'Agenda tu cita con SUConsulting',
    description:
    'Programa una reunión de orientación estratégica de forma simple, ordenada y con una experiencia clara desde el primer contacto.',
    calendarButtonUrl: 'https://calendar.google.com/',
    calendarEmbedUrl: '',
    steps: [
    'Selecciona el horario disponible que mejor se adapte a tu agenda.',
    'Completa tus datos básicos para registrar la solicitud de reunión.',
    'Recibe la confirmación y prepárate para una conversación estratégica.'
    ],
    policies: [
    'Agenda con anticipación para encontrar mejores espacios disponibles.',
    'Verifica tu correo para confirmar los datos de la reunión.',
    'Si no podrás asistir, cancela o reprograma con tiempo.',
    'Procura tener claridad básica sobre tu necesidad o reto empresarial.',
    'La reunión inicial busca orientar, diagnosticar y definir el siguiente paso.'
    ]
};