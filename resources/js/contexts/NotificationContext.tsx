// contexts/NotificationContext.tsx
import React, { createContext, useContext, useState, useCallback, useRef } from 'react';
import { useAnimationControls } from 'framer-motion'; // Import useAnimationControls

interface NotificationContextType {
    unreadMessages: number;
    messages: Message[];
    setUnreadMessages: (count: number | ((prev: number) => number)) => void;
    setMessages: (messages: Message[] | ((prev: Message[]) => Message[])) => void;
    addMessage: (message: Message) => void;
    markAsRead: () => void;
    // New: Animation controls and trigger function
    notificationControls: ReturnType<typeof useAnimationControls>;
    triggerNotificationAnimation: () => void;
}

interface Message {
    id: number;
    message: string;
    from: { name: string };
    created_at?: string;
}

const NotificationContext = createContext<NotificationContextType | undefined>(undefined);

export const useNotification = () => {
    const context = useContext(NotificationContext);
    if (context === undefined) {
        throw new Error('useNotification must be used within a NotificationProvider');
    }
    return context;
};

export const NotificationProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [unreadMessages, setUnreadMessages] = useState<number>(0);
    const [messages, setMessages] = useState<Message[]>([]);
    const notificationControls = useAnimationControls(); // Initialize controls here

    const addMessage = useCallback((message: Message) => {
        setMessages(prevMessages => {
            const messageExists = prevMessages.some(msg => msg.id === message.id);
            if (messageExists) {
                return prevMessages;
            }
            return [message, ...prevMessages];
        });
        setUnreadMessages(prev => prev + 1);
    }, []);

    const markAsRead = useCallback(() => {
        setUnreadMessages(0);
    }, []);

    // New: Function to trigger animation via controls
    const triggerNotificationAnimation = useCallback(() => {
        notificationControls.start({
            scale: [1, 1.5, 1],
            transition: {
                duration: 0.5,
                ease: 'easeInOut',
                repeat: 1,
                repeatType: 'mirror',
            },
        });
    }, [notificationControls]);

    return (
        <NotificationContext.Provider value={{
            unreadMessages,
            messages,
            setUnreadMessages,
            setMessages,
            addMessage,
            markAsRead,
            notificationControls, // Provide controls
            triggerNotificationAnimation // Provide trigger function
        }}>
            {children}
        </NotificationContext.Provider>
    );
};
