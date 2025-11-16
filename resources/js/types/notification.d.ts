export interface NotificationContextType {
    unreadMessages: number;
    messages: Message[];
    setUnreadMessages: (count: number | ((prev: number) => number)) => void;
    setMessages: (messages: Message[] | ((prev: Message[]) => Message[])) => void;
    addMessage: (message: Message) => void;
    markAsRead: () => void;
}

export interface Message {
    id: number;
    message: string;
    from: { name: string }; // 'from' now only has 'name' as avatar/email are not in the new event
    created_at?: string;
}

export // Updated interface to match the new backend response structure
export interface NewMessageEvent {
    conversation_id: number;
    message_id: number;
    receiver: {
        id: number;
        name: string;
        email: string;
        avatar: string | null;
    };
    message: { // This is the actual message content
        message: string;
        attachment_url: string | null;
        attachment_type: string | null;
        created_at: string;
    };
    unread_count: number;
    // IMPORTANT: Sender information (id, name, etc.) is missing in this event structure.
    // If you need the sender's name for the toast/message list,
    // your backend should include a 'sender' object here.
    // For now, a placeholder "A user" will be used for the sender's name.
}
