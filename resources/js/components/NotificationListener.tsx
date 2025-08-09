// components/NotificationListener.tsx
import { useEffect, useRef, useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { toast } from "sonner";
import { Howl } from 'howler';
import apiClient from '@/lib/apiClient';
import { setupEcho } from '@/utils/echo-setup';
import { useNotification } from '@/contexts/NotificationContext';

export interface NewMessageEvent {
    conversation_id: number;
    message_id: number;
    receiver: {
        id: number;
        name: string;
        email: string;
        avatar: string | null;
    };
    sender: {
        id: number;
        name: string;
        email: string;
        avatar: string | null;
    };
    message: {
        message: string;
        attachment_url: string | null;
        attachment_type: string | null;
        created_at: string;
    };
    unread_count: number;
}

export default function NotificationListener() {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;
    const echoRef = useRef<any>(null);
    // Get context actions and controls
    const { addMessage, setUnreadMessages, setMessages, triggerNotificationAnimation } = useNotification();

    // Initialize sound (only once)
    const soundRef = useRef<Howl | null>(null);
    useEffect(() => {
        if (!soundRef.current) {
            soundRef.current = new Howl({
                src: ['/media/bell.mp3'],
                volume: 0.5,
                html5: true, // Use HTML5 Audio to avoid some autoplay issues
            });
        }
    }, []);

    // Handle new message received
    const handleNewMessage = useCallback((data: NewMessageEvent) => {
        console.log('📨 New message received:', data);

        // Only process if this message is for the current user (receiver)
        if (data.receiver.id !== user?.id) {
            console.log('Message not for current user, ignoring');
            return;
        }

        const senderName = data.sender.name; // Placeholder for missing sender name

        const newMessage = {
            id: data.message_id,
            message: data.message.message,
            from: {
                name: senderName
            },
            created_at: data.message.created_at
        };

        addMessage(newMessage); // This also increments unread count

        // Play sound
        if (soundRef.current) {
            try {
                soundRef.current.play();
                console.log('Sound played');
            } catch (error) {
                console.error('Failed to play sound:', error);
            }
        } else {
            console.warn('Sound not initialized when trying to play.');
        }

        // Trigger animation via context
        triggerNotificationAnimation();
        console.log('Animation triggered via context');

        // Show toast notification
        try {
            toast.success(`New message from ${senderName}`, {
                description: newMessage.message.substring(0, 50) + (newMessage.message.length > 50 ? '...' : ''),
                duration: 4000,
                position: 'top-right',
            });
            console.log('Toast notification shown');
        } catch (error) {
            console.error('Failed to show toast:', error);
        }
    }, [addMessage, triggerNotificationAnimation, user?.id]);

    // Load initial unread messages
    useEffect(() => {
        if (user?.id) {
            console.log('Loading initial unread messages for user:', user.id);
            apiClient
                .post('/api/v1/get-unread-messages', {
                    user_id: user.id,
                })
                .then(res => {
                    console.log('Initial unread messages loaded:', res.data);
                    setUnreadMessages(res.data.length);

                    const transformedMessages = res.data.map((msg: any) => ({
                        id: msg.id,
                        message: msg.message,
                        from: {
                            name: msg.from?.name || msg.sender?.name || `User ${msg.sender_id}`
                        },
                        created_at: msg.created_at
                    }));

                    setMessages(transformedMessages);
                })
                .catch(error => {
                    console.error('Failed to load unread messages:', error);
                });
        }
    }, [user?.id, setUnreadMessages, setMessages]);

    // Set up Echo connection and listeners
    useEffect(() => {
        if (!user?.id) return;

        const authToken = auth.accessToken;
        const echo = setupEcho(authToken);
        if (!echo) {
            console.error('Failed to setup Echo');
            return;
        }

        echoRef.current = echo;
        const channelName = `notification.${user.id}`;

        try {
            const channel = echo.private(channelName);

            channel
                .subscribed(() => {
                    console.log('✅ Successfully subscribed to new message channel:', channelName);
                })
                .listen('.NewMessageNotification', (e: NewMessageEvent) => {
                    console.log('📨 NewMessage received to listener:', e);
                    handleNewMessage(e);
                })
                .error((error: any) => {
                    console.error('❌ Error subscribing to new message channel:', error);
                });

            echoRef.current = channel;

        } catch (error) {
            console.error('❌ Failed to setup new message channel:', error);
        }

        return () => {
            if (echoRef.current) {
                try {
                    echoRef.current.stopListening('.NewMessage');
                    console.log('Echo listener cleaned up');
                } catch (error) {
                    console.error('Error during cleanup:', error);
                }
            }
        };
    }, [user?.id, handleNewMessage, auth.accessToken]);

    return null;
}
