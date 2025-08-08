import { useState, useEffect, useCallback, useRef } from 'react';
import { Popover } from '@headlessui/react';
import { motion, useAnimation } from 'framer-motion';
import { Howl } from 'howler';
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { toast } from "sonner"
import apiClient from '@/lib/apiClient';
import type Echo from 'laravel-echo';
import { setupEcho } from '@/utils/echo-setup';
import { MessageSentEventResponse, TypingEventResponse } from '@/types/chat';
import { sortMessagesByDate } from '@/utils/chat-api';

interface Message {
    id: number;
    message: string;
    from: { name: string };
    created_at?: string;
}

interface NewMessageEvent {
    message: Message;
    recipient_id: number;
}

export default function NotificationPopover() {
    const [unreadMessages, setUnreadMessages] = useState<number>(0);
    const [messages, setMessages] = useState<Message[]>([]);
    const controls = useAnimation();
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;
    const echoRef = useRef<any>(null);

    // Initialize sound
    const sound = new Howl({
        src: ['/media/bell.mp3'],
        volume: 0.5,
    });

    // Trigger animation
    const triggerAnimation = useCallback(() => {
        controls.start({
            scale: [1, 1.5, 1],
            transition: {
                duration: 0.5,
                ease: 'easeInOut',
                repeat: 1,
                repeatType: 'mirror',
            },
        });
    }, [controls]);

    // Handle new message received
    const handleNewMessage = useCallback((data: NewMessageEvent) => {
        console.log('📨 New message received:', data);

        // Update unread count
        setUnreadMessages(prevUnread => prevUnread + 1);

        // Add new message to the list
        setMessages(prevMessages => [data.message, ...prevMessages]);

        // Play sound
        sound.play();

        // Trigger animation
        triggerAnimation();

        // Show toast notification
        toast.success(`New message from ${data.message.from.name}`, {
            description: data.message.message.substring(0, 50) + (data.message.message.length > 50 ? '...' : ''),
            duration: 4000,
        });
    }, [sound, triggerAnimation]);

    // Load initial unread messages
    useEffect(() => {
        if (user?.id) {
            apiClient
                .post('/api/v1/get-unread-messages', {
                    user_id: user.id,
                })
                .then(res => {
                    setUnreadMessages(res.data.length);
                    setMessages(res.data);
                })
                .catch(error => {
                    console.error('Failed to load unread messages:', error);
                });
        }
    }, [user?.id]);

    // Set up Echo connection and listeners
    useEffect(() => {
        if (!user?.id) return;

        // Get auth token (adjust this based on how you store it)
        const authToken = auth.accessToken;

        // Setup Echo using your existing function
        const echo = setupEcho(authToken);
        if (!echo) return;

        echoRef.current = echo;

        // Listen for new messages on user's private channel
        const channelName = `newmessage.${user.id}`;

        console.log('🔄 Attempting to join chat channel:', channelName);

        try {
            const channel = echoRef.current.private(channelName);
            echoRef.current = channel;
            channel
                .subscribed(() => {
                    console.log('✅ Successfully subscribed to chat channel:', channelName);
                    // setIsConnected(true);
                    // setError(null);
                })
                .listen('.NewMessage', (e: MessageSentEventResponse) => {
                    console.log('📨 NewMessage received:', e);
                })
                .error((error: any) => {
                    console.error('❌ Error subscribing to chat channel:', error);
                    // setError('Failed to connect to real-time chat');
                    // setIsConnected(false);
                });

        } catch (error) {
            console.error('❌ Failed to setup chat channel:', error);
            // setError('Failed to setup chat channel');
        }

        // Alternative: If you're using a different event name or channel structure
        // const channel = echo.private(`notifications.${user.id}`);
        // channel.listen('MessageReceived', handleNewMessage);

        // Cleanup function
        return () => {
            if (echoRef.current) {
                echoRef.current.stopListening('.NewMessage');
                // Optionally disconnect Echo if this is the only component using it
                // echoRef.current.disconnect();
            }
        };
    }, [user?.id, handleNewMessage]);

    // Mark messages as read when popover is opened
    const markAsRead = useCallback(() => {
        if (unreadMessages > 0 && user?.id) {
            apiClient
                .post('/api/v1/mark-messages-read', {
                    user_id: user.id,
                })
                .then(() => {
                    setUnreadMessages(0);
                })
                .catch(error => {
                    console.error('Failed to mark messages as read:', error);
                });
        }
    }, [unreadMessages, user?.id]);

    return (
        <Popover className="relative z-50">
            <Popover.Button
                className="relative rounded-full bg-blue-600 p-2 text-white hover:bg-blue-700 focus:outline-none"
                onClick={markAsRead}
            >
                <motion.div animate={controls}>
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" strokeWidth="2"
                         viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round"
                              d="M15 17h5l-1.405-1.405M19 13V9a7 7 0 10-14 0v4l-1.405 1.405M5 17h5m0 0v1a3 3 0 006 0v-1m-6 0h6"/>
                    </svg>
                    {unreadMessages > 0 && (
                        <motion.span
                            initial={{ scale: 0 }}
                            animate={{ scale: 1 }}
                            className="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full"
                        >
                            {unreadMessages > 99 ? '99+' : unreadMessages}
                        </motion.span>
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
                <div className="divide-y divide-gray-200 p-3 max-h-96 overflow-y-auto">
                    <div className="pb-2 mb-2 border-b">
                        <h3 className="text-sm font-medium text-gray-900">
                            Messages ({unreadMessages} unread)
                        </h3>
                    </div>

                    {messages.length === 0 ? (
                        <div className="text-sm text-gray-500 py-4 text-center">
                            No messages
                        </div>
                    ) : (
                        messages.map((msg) => (
                            <motion.div
                                key={msg.id}
                                initial={{ opacity: 0, y: -10 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="flex items-start gap-3 py-2"
                            >
                                <img
                                    src={`https://ui-avatars.com/api/?name=${msg.from.name}&size=64`}
                                    alt={msg.from.name}
                                    className="w-8 h-8 rounded-full"
                                />
                                <div className="flex flex-col flex-1">
                                    <p className="text-sm text-gray-800 line-clamp-2">
                                        {msg.message}
                                    </p>
                                    <span className="text-xs text-gray-400 mt-1">
                                        {msg.from.name}
                                    </span>
                                </div>
                            </motion.div>
                        ))
                    )}
                </div>
            </Popover.Panel>
        </Popover>
    );
}
