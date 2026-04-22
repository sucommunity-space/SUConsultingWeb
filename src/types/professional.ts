export interface Professional {
    id: string;
    slug: string;
    name: string;
    role: string;
    specialties: string[];
    bio: string;
    image: string;
    linkedin?: string;
    featured?: boolean;
}