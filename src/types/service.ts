export interface Service {
    id: string;
    slug: string;
    title: string;
    shortDescription: string;
    fullDescription: string;
    benefits: string[];
    icon: string;
    featured?: boolean;
}