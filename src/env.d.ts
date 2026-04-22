interface TurnstileApi {
    render: (container: string | HTMLElement, options?: Record<string, unknown>) => string;
    reset: (widgetId?: string) => void;
    remove: (widgetId?: string) => void;
    getResponse: (widgetId?: string) => string;
}

interface Window {
    turnstile?: TurnstileApi;
}