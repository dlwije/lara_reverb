import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Declare global Echo
declare global {
    interface Window {
        Pusher: typeof Pusher
        Echo: Echo<T>
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
    const idA = Math.min(userIdA, userIdB)
    const idB = Math.max(userIdA, userIdB)
    return `chat.${idA}-${idB}`
}

// Message interface from Laravel backend
export interface LaravelMessage {
    message: {
        sender_id: number
        receiver_id: number
        created_at: string
        message: string
    }
}

// Typing event interface
export interface TypingEvent {
  user_id: number
  user_name: string
  typing: boolean
}

// Convert Laravel message to our frontend format
export function convertLaravelMessage(
    laravelMsg: LaravelMessage,
    currentUserId: number
): {
    id: number
    user_id: number
    from: number
    message: string
    created_at: string
    updated_at: string
    isUser: boolean
} {
  return {
    id: laravelMsg.message.id || Date.now(), // Use backend ID if available
    user_id: laravelMsg.message.receiver_id,
    from: laravelMsg.message.sender_id,
    message: laravelMsg.message.message,
    created_at: laravelMsg.message.created_at,
    updated_at: laravelMsg.message.created_at,
    isUser: laravelMsg.message.sender_id === currentUserId
  }
}
