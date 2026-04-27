export type NavigationItem = {
    label: string;
    href: string;
  };
  
  export const navigation: NavigationItem[] = [
    { label: "Inicio", href: "/" },
    { label: "Servicios", href: "/servicios" },
    { label: "Nosotros", href: "/quienes-somos" },
    { label: "Profesionales", href: "/profesionales" },
    { label: "Contacto", href: "/contacto" }
  ];