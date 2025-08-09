import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Declare global Echo
declare global {
    interface Window {
        Pusher: typeof Pusher
        Echo: Echo<'reverb'>
    }
}

// Setup Laravel Echo
export function setupEcho(authToken?: string) {
    if (typeof window === 'undefined') return null

    window.Pusher = Pusher

    const echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        authEndpoint: `${import.meta.env.VITE_APP_URL}/api/broadcasting/auth`,
        auth: {  // ✅ Correct placement for auth headers
            headers: {
                Authorization: `Bearer ${authToken ?? localStorage.getItem('acc_token') ?? ''}`,
            },
        },
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT,
        wssPort: import.meta.env.VITE_REVERB_PORT,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    return echo
}

// Create channel name for two users
export function createChannelName(userIdA: number, userIdB: number): string {
    // const idA = Math.min(userIdA, userIdB)
    // const idB = Math.max(userIdA, userIdB)
    return `chat.${[userIdA, userIdB].sort().join('-')}`;
    // return `chat.${idA}-${idB}`
}
