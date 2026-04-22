export interface BookingConfig {
    title: string;
    description: string;
    calendarEmbedUrl?: string;
    calendarButtonUrl: string;
    steps: string[];
    policies: string[];
}