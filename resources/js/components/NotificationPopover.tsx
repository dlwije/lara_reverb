import { useState, useEffect } from 'react';
import { Popover } from '@headlessui/react';
import { motion, useAnimation } from 'framer-motion';
import useEcho from '@/lib/echo';
import { Howl } from 'howler';
import { axios } from '@/lib/axios';
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { toast } from "sonner"

interface User {
    id: number
    name: string
    email: string
    avatar?: string | null
    // email_verified_at: string | null
    // created_at: string
    // status: string
    // gender: string | null
    // address: string | null
    // dob: string | null
    // nationality: string | null
    // marital_status: string | null
    // occupation: string | null
    phone: string | null
    user_type: string
    updated_at: string
    role: string
    DT_RowIndex: number
}
export default function NotificationPopover() {
    const [unreadMessages, setUnreadMessages] = useState<number>(0);
    const [messages, setMessages] = useState<{ id: number; message: string; from: { name: string } }[]>([]);
    const controls = useAnimation();
    const [onlineUsers, setOnlineUsers] = useState<User[]>([]);

    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    const echo = useEcho()

    const sound = new Howl({
        src: ['/media/bell.mp3'],
    })

    // Trigger chat message count
    const triggerAnimation = () => {
        controls.start({
            scale: [1, 1.5, 1],
            transition: {
                duration: 0.5,
                ease: 'easeInOut',
                repeat: 1,
                repeatType: 'mirror',
            },
        })
    }

    // eslint-disable-next-line react-hooks/exhaustive-deps
    const handleEchoCallback = () => {
        setUnreadMessages(prevUnread => prevUnread + 1)
        triggerAnimation()
        sound.play()
    }

    useEffect(() => {
        // Here we are going to listen for real-time events.
        if (echo) {
            // 1. Join the channel ONCE and store the instance.
            const channel = echo.join('online');
            console.log('Attempting to join channel:', channel);

            // 2. Chain all event listeners to this single instance.
            channel
                .subscribed(() => {
                    // This confirms the WebSocket connection AND authorization were successful.
                    console.log(
                        '✅ Successfully subscribed to the "online" presence channel!'
                    );
                })
                .here((members: User[]) => {
                    // This is called immediately after a successful subscription.
                    console.log('👥 Users currently here:', members);
                    const withUnread = members.map((user) => ({
                        ...user,
                        unread: Math.random() > 0.5
                    }));
                    setOnlineUsers(withUnread);
                })
                .joining((user: User) => {
                    console.log('➕ User joining:', user);
                    setOnlineUsers((prev) => [...prev, { ...user, unread: true }]);
                })
                .leaving((user: User) => {
                    console.log('➖ User leaving:', user);
                    setOnlineUsers((prev) => prev.filter((u) => u.id !== user.id));
                })
                .error((error: any) => {
                    // This is crucial for debugging auth issues!
                    console.error('Subscription Error:', error);
                });

            // 3. Cleanup: Leave the channel when the component unmounts.
            return () => {
                console.log('Leaving "online" channel.');
                echo?.leave('online');
            };
        }
    }, [echo])

    useEffect(() => {
        axios
            .post('/api/v1/get-unread-messages', {
                user_id: user?.id,
            })
            .then(res => {
                setUnreadMessages(res.data.length)
                setMessages(res.data)
            })
    }, [user?.id])

    return (
        <Popover className="relative z-50">
            <Popover.Button className="relative rounded-full bg-blue-600 p-2 text-white hover:bg-blue-700 focus:outline-none">
                <motion.div animate={controls}>
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" strokeWidth="2"
                         viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round"
                              d="M15 17h5l-1.405-1.405M19 13V9a7 7 0 10-14 0v4l-1.405 1.405M5 17h5m0 0v1a3 3 0 006 0v-1m-6 0h6"/>
                    </svg>
                    {unreadMessages > 0 && (
                        <span
                            className="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full">
              {unreadMessages}
            </span>
                    )}
                </motion.div>
            </Popover.Button>

            <Popover.Panel
                as={motion.div}
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: 20 }}
                className="absolute bottom-full rounded-md right-0 mb-3 w-80 translate-x-24 bg-white shadow-lg ring-1 ring-black ring-opacity-5"
            >
                <div className="divide-y divide-gray-200 p-3">
                    {messages.length === 0 ? (
                        <div className="text-sm text-gray-500">No messages</div>
                    ) : (
                        messages.map((msg) => (
                            <div key={msg.id} className="flex items-start gap-3 py-2">
                                <img
                                    src={`https://ui-avatars.com/api/?name=${msg.from.name}&size=64`}
                                    alt={msg.from.name}
                                    className="w-8 h-8 rounded-full"
                                />
                                <div className="flex flex-col">
                                    <p className="text-sm text-gray-800">{msg.message}</p>
                                    <span className="text-xs text-gray-400 text-right">{msg.from.name}</span>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </Popover.Panel>
        </Popover>
    );
}
