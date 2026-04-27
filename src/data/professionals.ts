import type { Professional } from '../types/professional';

export const professionals: Professional[] = [
  {
    id: 'manuel-espinoza',
    slug: 'manuel-espinoza',
    name: 'Diego Zorrilla Green',
    role: 'Consultor estratégico',
    specialties: ['Gestión empresarial', 'Tecnología', 'Estrategia'],
    bio: 'Perfil orientado al acompañamiento estratégico, estructura de servicios y fortalecimiento organizacional.',
    image: '/images/professionals/professional-1.webp',
    featured: true
  },
  {
    id: 'xiomara-mejia',
    slug: 'xiomara-mejia',
    name: 'Xiomara Mejia Figueroa',
    role: 'Consultora de soporte y coordinación',
    specialties: ['Gestión', 'Organización', 'Atención consultiva'],
    bio: 'Perfil enfocado en soporte organizacional, coordinación de procesos y acompañamiento de iniciativas empresariales.',
    image: '/images/professionals/professional-2.webp',
    featured: true
  },
  {
    id: 'equipo-asesor-1',
    slug: 'equipo-asesor-1',
    name: 'Asesor Especializado 01',
    role: 'Consultor asociado',
    specialties: ['Finanzas', 'Contabilidad', 'Análisis'],
    bio: 'Profesional asociado para acompañamiento en áreas financieras y mejora de gestión.',
    image: '/images/professionals/professional-4.webp'
  },
  {
    id: 'equipo-asesor-2',
    slug: 'equipo-asesor-2',
    name: 'Asesor Especializado 02',
    role: 'Consultor asociado',
    specialties: ['ISO', 'Procesos', 'Mejora continua'],
    bio: 'Especialista de apoyo en organización de procesos y enfoque de mejora continua.',
    image: '/images/professionals/professional-5.webp'
  }
];
