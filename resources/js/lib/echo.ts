import Echo from "laravel-echo";
import { useEffect, useState } from 'react';

const useEcho = () => {
    const [echoInstance, setEchoInstance] = useState<Echo<T> | null>(null);

    useEffect(() => {
        const token = localStorage.getItem('acc_token'); // Passport token
        // console.log('Token:', token); // Debug

        const echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            authEndpoint: `${import.meta.env.VITE_APP_URL}/api/broadcasting/auth`,
            auth: {  // ✅ Correct placement for auth headers
                headers: {
                    Authorization: `Bearer ${token}`,
                },
            },
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT,
            wssPort: import.meta.env.VITE_REVERB_PORT,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        setEchoInstance(echo);

        return () => {
            echo.disconnect(); // Cleanup on unmount
        };
    }, []);

    return echoInstance;
};

export default useEcho;
