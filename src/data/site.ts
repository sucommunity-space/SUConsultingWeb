import type { SiteConfig } from '../types/site';

export const site: SiteConfig = {
    name: 'SUConsulting',
    legalName: 'SUC CORPORATION SAC',
    description:
    'Firma consultiva orientada a fortalecer empresarios, emprendedores y organizaciones mediante acompañamiento estratégico, principios sólidos y visión empresarial.',
    url: 'https://suconsulting.pe',
    email: 'contacto@suconsulting.pe',
    phone: '+51 987 654 321',
    address: 'Av. Carlos Ferreyros 444, San Isidro, Lima',
    nav: [
    { label: 'Inicio', href: '/' },
    { label: '¿Quiénes somos?', href: '/quienes-somos' },
    { label: 'Servicios', href: '/servicios' },
    { label: 'Profesionales', href: '/profesionales' },
    { label: 'Agendar cita', href: '/agendar-cita' },
    { label: 'Contacto', href: '/contacto' }
    ],
    socials: {
    linkedin: '',
    facebook: '',
    instagram: '',
    whatsapp: ''
    }
};