export interface NavItem {
    label: string;
    href: string;
}

export interface SiteConfig {
    name: string;
    legalName: string;
    description: string;
    url: string;
    email: string;
    phone: string;
    address?: string;
    nav: NavItem[];
    socials: {
        linkedin?: string;
        facebook?: string;
        instagram?: string;
        whatsapp?: string;
    };
}