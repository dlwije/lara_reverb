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
    const channelRef = useRef<any>(null);
    const { addMessage, setUnreadMessages, setMessages, triggerNotificationAnimation } = useNotification();

    // Initialize sound with better error handling
    const soundRef = useRef<Howl | null>(null);
    useEffect(() => {
        if (!soundRef.current) {
            try {
                soundRef.current = new Howl({
                    src: ['/media/bell.mp3'],
                    volume: 0.5,
                    html5: true,
                    preload: true,
                    onloaderror: (id, error) => {
                        console.error('Failed to load notification sound:', error);
                    },
                    onplayerror: (id, error) => {
                        console.error('Failed to play notification sound:', error);
                    }
                });
                console.log('🔔 Notification sound initialized');
            } catch (error) {
                console.error('❌ Failed to initialize notification sound:', error);
            }
        }
    }, []);

    // Handle new message received with better error handling
    const handleNewMessage = useCallback((data: NewMessageEvent) => {
        console.log('📨 New message received:', data);

        // Validate data
        if (!data || !data.receiver || !data.sender || !data.message) {
            console.error('❌ Invalid message data received:', data);
            return;
        }

        // Only process if this message is for the current user
        if (data.receiver.id !== user?.id) {
            console.log('👤 Message not for current user, ignoring');
            return;
        }

        const senderName = data.sender.name || `User ${data.sender.id}`;

        const newMessage = {
            id: data.message_id,
            message: data.message.message,
            from: {
                name: senderName
            },
            created_at: data.message.created_at,
            conversation_id: data.conversation_id,
            sender_id: data.sender.id
        };

        try {
            // Add message to context
            addMessage(newMessage);
            console.log('✅ Message added to notification context');

            // Play sound
            if (soundRef.current) {
                try {
                    soundRef.current.play();
                    console.log('🔊 Sound played');
                } catch (error) {
                    console.error('❌ Failed to play sound:', error);
                }
            }

            // Trigger animation
            triggerNotificationAnimation();
            console.log('🎬 Animation triggered');

            // Show toast notification
            toast.success(`New message from ${senderName}`, {
                description: data.message.message.substring(0, 50) + (data.message.message.length > 50 ? '...' : ''),
                duration: 4000,
                position: 'top-right',
                action: {
                    label: 'View',
                    onClick: () => {
                        // You can add navigation logic here
                        console.log('Navigating to conversation:', data.conversation_id);
                    }
                }
            });

        } catch (error) {
            console.error('❌ Error processing new message:', error);
        }
    }, [addMessage, triggerNotificationAnimation, user?.id]);

    // Load initial unread messages
    const loadUnreadMessages = useCallback(async () => {
        if (!user?.id) return;

        try {
            console.log('📥 Loading initial unread messages for user:', user.id);
            const response = await apiClient.get(`/api/v1/get-unread-messages/${user.id}`, {
                params: { user_id: user.id }
            });

            console.log('✅ Initial unread messages loaded:', response.data);

            if (response.data && Array.isArray(response.data.data)) {
                setUnreadMessages(response.data.data.length);

                const transformedMessages = response.data.data.map((msg: any) => ({
                    id: msg.id,
                    message: msg.message,
                    from: {
                        name: msg.from?.name || msg.sender?.name || `User ${msg.sender_id}`
                    },
                    created_at: msg.created_at,
                    conversation_id: msg.conversation_id,
                    sender_id: msg.sender_id
                }));

                setMessages(transformedMessages);
            }
        } catch (error) {
            console.error('❌ Failed to load unread messages:', error);
        }
    }, [user?.id, setUnreadMessages, setMessages]);

    // Load unread messages on mount
    useEffect(() => {
        loadUnreadMessages();
    }, [loadUnreadMessages]);

    // Set up Echo connection and listeners
    useEffect(() => {
        if (!user?.id || !auth.accessToken) {
            console.log('⏸️ Skipping Echo setup: missing user ID or auth token');
            return;
        }

        let isSubscribed = false;

        const initializeEcho = async () => {
            try {
                const echo = setupEcho(auth.accessToken);
                if (!echo) {
                    console.error('❌ Failed to setup Echo');
                    return;
                }

                echoRef.current = echo;
                const channelName = `notification.${user.id}`;

                console.log('🔄 Setting up notification channel:', channelName);

                const channel = echo.private(channelName);

                channel
                    .subscribed(() => {
                        isSubscribed = true;
                        console.log('✅ Successfully subscribed to notification channel:', channelName);
                    })
                    .listen('.NewMessageNotification', (e: NewMessageEvent) => {
                        console.log('📨 NewMessageNotification event received:', e);
                        handleNewMessage(e);
                    })
                    .error((error: any) => {
                        console.error('❌ Error in notification channel:', error);
                        isSubscribed = false;
                    });

                channelRef.current = channel;

            } catch (error) {
                console.error('❌ Failed to setup notification channel:', error);
            }
        };

        initializeEcho();

        // Cleanup function
        return () => {
            console.log('🧹 Cleaning up notification listener');

            if (channelRef.current) {
                try {
                    channelRef.current.stopListening('.NewMessageNotification');
                    console.log('🔌 Stopped listening to notification events');
                } catch (error) {
                    console.error('Error stopping listener:', error);
                }
            }

            if (echoRef.current && isSubscribed) {
                try {
                    echoRef.current.leave(`notification.${user.id}`);
                    console.log('🚪 Left notification channel');
                } catch (error) {
                    console.error('Error leaving channel:', error);
                }
            }
        };
    }, [user?.id, auth.accessToken, handleNewMessage]);

    return null;
}
