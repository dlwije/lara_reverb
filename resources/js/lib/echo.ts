import { axios, setBearerToken } from '@/lib/axios';
import Echo from "laravel-echo";
import { useMemo } from 'react';
import apiClient from '@/lib/apiClient';

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

                // authorize: async (socketId, callback) => {
                //     await apiClient.post('/api/broadcasting/auth', {
                //         socket_id: socketId,
                //         channel_name: channel.name,
                //     }).then((res) => callback(false, res.data))
                //         .catch((err) => callback(true, err))
                // }
            })
        })
        // return new Echo({
        //     broadcaster: 'pusher',
        //     key: import.meta.env.VITE_REVERB_APP_KEY || 'local',
        //     wsHost: import.meta.env.VITE_REVERB_HOST,
        //     // eslint-disable-next-line no-constant-binary-expression
        //     wsPort: Number(import.meta.env.VITE_REVERB_PORT) ?? 80,
        //     // eslint-disable-next-line no-constant-binary-expression
        //     wssPort: Number(import.meta.env.VITE_REVERB_PORT) ?? 443,
        //     forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        //     disableStats: true,
        //     enabledTransports: ['ws', 'wss'],
        //     cluster: 'mt1',
        //     authEndpoint: `${import.meta.env.APP_URL}/api/broadcasting/auth`,
        //     auth: {
        //         headers: {
        //             Authorization: `Bearer ${localStorage.getItem('acc_token')}`
        //         }
        //     },
        //     withCredentials: true
        // });
    }, [])
}

export default useEcho
