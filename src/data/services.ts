import type { Service } from '../types/service';

export const services: Service[] = [
  {
    id: 'gestion-comercial-finanzas',
    slug: 'gestion-comercial-finanzas',
    title: 'Consultoría en gestión comercial, contabilidad y finanzas',
    shortDescription:
      'Fortalecemos la gestión empresarial con una mirada estratégica, financiera y comercial.',
    fullDescription:
      'Acompañamos a organizaciones que necesitan mayor claridad en su gestión comercial, contable y financiera, ayudándolas a ordenar procesos, revisar indicadores y mejorar la toma de decisiones.',
    benefits: [
      'Mayor control financiero',
      'Mejor lectura del negocio',
      'Orden en procesos comerciales'
    ],
    icon: 'briefcase',
    featured: true
  },
  {
    id: 'planes-negocio',
    slug: 'planes-negocio',
    title: 'Diseño de planes de negocio para emprendedores',
    shortDescription:
      'Convertimos ideas y proyectos en planes mejor estructurados y orientados a crecimiento.',
    fullDescription:
      'Diseñamos planes de negocio para emprendedores que necesitan aterrizar su propuesta, organizar prioridades y presentar una ruta empresarial más clara.',
    benefits: [
      'Claridad estratégica',
      'Definición de propuesta de valor',
      'Ruta de crecimiento inicial'
    ],
    icon: 'file-text',
    featured: true
  },
  {
    id: 'continuidad-empresas-familiares',
    slug: 'continuidad-empresas-familiares',
    title: 'Planes de continuidad de negocio para empresas familiares',
    shortDescription:
      'Ayudamos a preparar organizaciones familiares para sostener su desarrollo con orden.',
    fullDescription:
      'Acompañamos a empresas familiares en la construcción de planes de continuidad que permitan fortalecer estructura, roles, visión y sostenibilidad en el tiempo.',
    benefits: [
      'Mayor continuidad operativa',
      'Mejor preparación organizacional',
      'Visión de largo plazo'
    ],
    icon: 'refresh-cw',
    featured: true
  },
  {
    id: 'sistemas-gestion-iso',
    slug: 'sistemas-gestion-iso',
    title: 'Implementación de sistemas de gestión para normas ISO',
    shortDescription:
      'Soporte para estructurar procesos alineados a estándares de gestión y mejora continua.',
    fullDescription:
      'Apoyamos la implementación de sistemas de gestión orientados a normas ISO, fortaleciendo estructura documental, orden operativo y enfoque de mejora.',
    benefits: [
      'Procesos más ordenados',
      'Mejora continua',
      'Preparación para estándares'
    ],
    icon: 'shield-check'
  },
  {
    id: 'soluciones-tecnologicas',
    slug: 'soluciones-tecnologicas',
    title: 'Soluciones tecnológicas empresariales',
    shortDescription:
      'Impulsamos herramientas y soluciones tecnológicas alineadas a necesidades reales del negocio.',
    fullDescription:
      'Ayudamos a evaluar e integrar soluciones tecnológicas empresariales que aporten eficiencia, soporte operativo y mejor gestión de información.',
    benefits: [
      'Mejor soporte a procesos',
      'Mayor eficiencia',
      'Alineación tecnología-negocio'
    ],
    icon: 'cpu'
  }
];