import { axios, setBearerToken } from '@/lib/axios';
import Echo from "laravel-echo";
import { useMemo } from 'react';

const token = localStorage.getItem('acc_token'); // Passport token
setBearerToken(token);

const useEcho = () => {
    return useMemo(() => {
        return new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            authorizer: (channel) => ({
                authorize: (socketId, callback) => {
                    axios
                        .post('/api/broadcasting/auth', {
                            socket_id: socketId,
                            channel_name: channel.name,
                        })
                        .then((res) => callback(false, res.data))
                        .catch((err) => callback(true, err))
                }
            })
        })
    }, [])
}

export default useEcho
