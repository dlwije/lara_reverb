export interface BackendMessage {
    id: number
    user_id: number
    from: number
    message: string
    created_at: string
    updated_at: string
    isUser: boolean
}

export interface Conversation {
    conversation_id: number
    last_message_at: string
    last_message: string
    unread_count: number
    user: User
}

export interface ApiResponse {
    status: boolean
    message: string
    data: BackendMessage[]
}

interface ConversationsResponse {
    status: boolean
    message: string
    data: Conversation[]
}

export interface SendMessagePayload {
    conversation_id?: number
    user_id: number
    from: number
    message: string
}

export interface SendMessageResponse {
    status: boolean
    message: string
    data: BackendMessage
}

// Types for typing event
export interface SendTypingPayload {
    user_id: number
    channel: string
    typing: boolean
    conversation_id?: number // if you need to associate with a specific conversation
}

export interface SendTypingResponse {
    status: boolean
    message: string
    data?: any
}

// Message interface from Laravel backend
export interface MessageSentEventResponse {
    message: {
        id: number
        sender_id: number
        receiver_id: number
        created_at: string
        updated_at: string
        message: string
    }
}

// Typing event interface
export interface TypingEventResponse {
    user_id: number
    user_name: string
    typing: boolean
}

export interface convertMessageSentEventResponseProps{
    id: number
    user_id: number
    from: number
    message: string
    created_at: string
    updated_at: string
    isUser: boolean
}
