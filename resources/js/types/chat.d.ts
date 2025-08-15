// Base user interface for chat functionality
export interface ChatUser {
  id: number
  name: string
  email: string
  avatar?: string | null
}

// Extended user interface for data tables and full user management
export interface User extends ChatUser {
  phone?: string | null
  user_type?: string
  updated_at?: string
  role?: string
  DT_RowIndex?: number
}

export interface BackendMessage {
    id: number
    name: number
    from: number
    message: string
    created_at: string
    updated_at: string
    isUser: boolean
}

interface MessageReceiver {
    id: number
    name: string
    email: string
    avatar?: string | null
}

interface MessageSender {
    id: number
    name: string
    email: string
    avatar?: string | null
}

export interface Conversation {
  conversation_id: number
  last_message_at: string
  last_message: string
  unread_count: number
  user: ChatUser // Use ChatUser here since conversations only need basic user info
  user2: ChatUser // Use ChatUser here since conversations only need basic user info
  // sender: MessageSender
  // receiver: MessageReceiver
}

export interface ApiResponse {
    status: boolean
    message: string
    data: BackendMessage[]
}

export interface ConversationsResponse {
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
