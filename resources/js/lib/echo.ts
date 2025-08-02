import axios from "axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

// Ensure TypeScript recognizes Pusher globally
declare global {
    interface Window {
        Pusher: typeof Pusher;
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-expect-error
        Echo: Echo;
    }
}
window.Pusher = Pusher;
window.Echo = new Echo({
    auth: { headers: undefined },
    authEndpoint: '',
    bearerToken: undefined,
    csrfToken: undefined,
    host: undefined,
    namespace: undefined,
    userAuthentication: { endpoint: '', headers: undefined },
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY as string, // Explicitly cast environment variable
    authorizer: (channel) => {
        console.log('ChannelName: '+channel.name);
        return {
            authorize: (socketId: string, callback: (error: boolean, data: never) => void) => {
                console.log('SocketId: '+socketId);
                axios
                    .post("api/broadcasting/auth", {
                        socket_id: socketId,
                        channel_name: channel.name,
                    },{
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem('token')}`,
                        },
                    })
                    .then((response) => {
                        callback(false, response.data);
                    })
                    .catch((error) => {
                        callback(true, error);
                    });
            },
        };
    },
    wsHost: import.meta.env.VITE_REVERB_HOST as string,
    wsPort: (import.meta.env.VITE_REVERB_PORT as unknown as number) ?? 80,
    wssPort: (import.meta.env.VITE_REVERB_PORT as unknown as number) ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"]
});
export default window.Echo;
