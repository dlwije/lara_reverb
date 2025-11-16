import axios, { AxiosResponse } from 'axios'
import apiClient from '@/lib/apiClient';
import {
    ApiResponse,
    BackendMessage,
    Conversation,
    ConversationsResponse,
    SendMessagePayload,
    SendMessageResponse,
    SendTypingResponse,
} from '@/types/chat';

// Helper function to get route - you'll need to implement this based on your routing system
// function route(routeName: string): string {
//   const routes: Record<string, string> = {
//     "api.v1.conversation.store": "/api/v1/conversations",
//     "api.v1.typing.store": "/api/v1/typing",
//   }
//   return routes[routeName] || routeName
// }

// Utility function to fetch messages by conversation ID
export async function fetchMessagesByConversation(conversationId: number): Promise<BackendMessage[]> {
    try {
        console.log("🔄 Fetching messages for conversation ID:", conversationId)
        const response: AxiosResponse<ApiResponse> = await apiClient.get(`api/v1/conversations/${conversationId}/messages`)

        console.log("📥 API Response:", response.data)

        if (!response.data.status) {
            throw new Error(response.data.message || "Failed to fetch messages")
        }
        console.log("✅ Successfully fetched", response.data.data.length, "messages")
        return response.data.data
    } catch (error) {
        if (axios.isAxiosError(error)) {
            console.error("Axios error fetching messages:", {
                message: error.message,
                status: error.response?.status,
                data: error.response?.data,
            })

            if (error.response?.status === 404) {
                console.warn("Conversation not found - using empty array")
                return []
            }

            if (error.code === "ECONNABORTED") {
                throw new Error("Request timeout - please check your connection")
            }

            throw new Error(error.response?.data?.message || "Failed to fetch messages")
        }

        console.error("Unexpected error fetching messages:", error)
        return []
    }
}


// Fetch conversations with unread messages for logged-in user
export async function fetchConversations(userId: number): Promise<Conversation[]> {
    try {
        console.log("🔄 Fetching conversations for user ID:", userId)
        const response: AxiosResponse<ConversationsResponse> = await apiClient.get(`/api/v1/get-unread-messages/${userId}`)

        console.log("📥 Conversations API Response:", response.data)

        if (!response.data.status) {
            throw new Error(response.data.message || "Failed to fetch conversations")
        }

        // Process conversations to ensure we get the OTHER participant's data
        const processedConversations = processConversations(response.data.data, userId)

        console.log("✅ Successfully fetched and processed", processedConversations.length, "conversations")
        return processedConversations
    } catch (error) {
        if (axios.isAxiosError(error)) {
            console.error("Axios error fetching conversations:", {
                message: error.message,
                status: error.response?.status,
                data: error.response?.data,
            })

            if (error.response?.status === 404) {
                console.warn("Conversations not found - using empty array")
                return []
            }

            if (error.code === "ECONNABORTED") {
                throw new Error("Request timeout - please check your connection")
            }

            throw new Error(error.response?.data?.message || "Failed to fetch conversations")
        }

        console.error("Unexpected error fetching conversations:", error)
        return []
    }
}

// Legacy function - keep for backward compatibility
export async function fetchMessages(userId: number): Promise<BackendMessage[]> {
    try {
        console.log('🔄 Fetching messages for user ID on chat-api:', userId)
        const response: AxiosResponse<ApiResponse> = await apiClient.get(`/api/v1/get-conversation/${userId}`)

        console.log('📥 API Response:', response.data)

        if (!response.data.status) {
            throw new Error(response.data.message || 'Failed to fetch messages')
        }


        console.log('✅ Successfully fetched', response.data.data.length, 'messages')
        return response.data.data
    } catch (error) {
        if (axios.isAxiosError(error)) {
            console.error('Axios error fetching messages:', {
                message: error.message,
                status: error.response?.status,
                data: error.response?.data
            })

            if (error.response?.status === 404) {
                console.warn('Messages endpoint not found - using empty array')
                return []
            }

            if (error.code === 'ECONNABORTED') {
                throw new Error('Request timeout - please check your connection')
            }

            throw new Error(error.response?.data?.message || 'Failed to fetch messages')
        }

        console.error('Unexpected error fetching messages:', error)
        return []
    }
}

// Utility function to send a message to your backend
export async function sendMessageToBackend(payload: SendMessagePayload): Promise<BackendMessage | null> {
    try {
        console.log('📤 Sending message:', payload)
        const response: AxiosResponse<SendMessageResponse> = await apiClient.post(route('api.v1.conversation.store'), payload)

        console.log('📤 Send message response:', response.data)

        if (!response.data.status) {
            throw new Error(response.data.message || 'Failed to send message')
        }

        return response.data.data
    } catch (error) {
        if (axios.isAxiosError(error)) {
            console.error('Axios error sending message:', {
                message: error.message,
                status: error.response?.status,
                data: error.response?.data
            })

            if (error.code === 'ECONNABORTED') {
                throw new Error('Request timeout - please try again')
            }

            throw new Error(error.response?.data?.message || 'Failed to send message')
        }

        console.error('Unexpected error sending message:', error)
        return null
    }
}

// Utility function to get or create conversation
export async function getOrCreateConversation(
    userId1: number,
    userId2: number,
): Promise<{ id: number; exists: boolean }> {
    try {
        console.log("🔄 Getting/creating conversation between users:", userId1, userId2)
        const response = await apiClient.post("/api/v1/conversations/get-or-create", {
            user1_id: userId1,
            user2_id: userId2,
        })

        console.log("📥 Conversation response:", response.data)

        if (!response.data.status) {
            throw new Error(response.data.message || "Failed to get conversation")
        }

        return {
            id: response.data.data.id,
            exists: response.data.data.exists || false,
        }
    } catch (error) {
        if (axios.isAxiosError(error)) {
            console.error("Axios error getting conversation:", error.response?.data)
            throw new Error(error.response?.data?.message || "Failed to get conversation")
        }

        console.error("Unexpected error getting conversation:", error)
        throw error
    }
}


// Mark conversation as read
export async function markConversationAsRead(conversationId: number): Promise<boolean> {
    try {
        console.log("🔄 Marking conversation as read:", conversationId)
        const response = await apiClient.post(`/api/v1/conversations/${conversationId}/mark-read`)

        console.log("📥 Mark as read response:", response.data)

        if (!response.data.status) {
            throw new Error(response.data.message || "Failed to mark conversation as read")
        }

        return true
    } catch (error) {
        if (axios.isAxiosError(error)) {
            console.error("Axios error marking conversation as read:", error.response?.data)
            return false
        }

        console.error("Unexpected error marking conversation as read:", error)
        return false
    }
}

// NEW: Process conversations to ensure we get the OTHER participant's data
function processConversations(conversations: any[], currentUserId: number): Conversation[] {
    return conversations.map((conv) => {
        console.log("🔍 Processing conversation:", conv)

        // If the conversation has participants array, find the other user
        if (conv.participants && Array.isArray(conv.participants)) {
            const otherParticipant = conv.participants.find((p: any) => p.id !== currentUserId)
            if (otherParticipant) {
                return {
                    ...conv,
                    user: {
                        id: otherParticipant.id,
                        name: otherParticipant.name,
                        email: otherParticipant.email,
                        avatar: otherParticipant.avatar,
                    },
                }
            }
        }

        // If the conversation has user1 and user2 fields
        if (conv.user1 && conv.user2) {
            const otherUser = conv.user1.id === currentUserId ? conv.user2 : conv.user1
            return {
                ...conv,
                user: {
                    id: otherUser.id,
                    name: otherUser.name,
                    email: otherUser.email,
                    avatar: otherUser.avatar,
                },
            }
        }

        // If the conversation already has a user field, check if it's the current user
        if (conv.user && conv.user.id === currentUserId) {
            console.warn("⚠️ Conversation contains current user data instead of other participant:", conv)
            // Try to find other participant data in the conversation object
            if (conv.other_user) {
                return {
                    ...conv,
                    user: conv.other_user,
                }
            }
        }

        console.log("✅ Processed conversation user:", conv.user)
        return conv
    })
}

// Utility function to send typing event to your backend
export async function sendTypingEventToBackend(payload: {
    user_id: number;
    channel: string | null;
    typing: boolean
}): Promise<boolean> {
    try {
        console.log('⌨️ Sending typing event:', payload)

        const response: AxiosResponse<SendTypingResponse> = await apiClient.post(
            route('api.v1.typing.store'), // or whatever your Laravel route is
            payload
        )

        console.log('⌨️ Typing event response:', response.data)

        if (!response.data.status) {
            throw new Error(response.data.message || 'Failed to send typing event')
        }

        return true
    } catch (error) {
        if (axios.isAxiosError(error)) {
            console.error('Axios error sending typing event:', {
                message: error.message,
                status: error.response?.status,
                data: error.response?.data
            })

            // Don't throw errors for typing events - they're not critical
            // Just log and return false
            return false
        }

        console.error('Unexpected error sending typing event:', error)
        return false
    }
}

// Utility function to determine if message is from current user
export function determineIsUser(message: BackendMessage, currentUserId: number): BackendMessage {
    return {
        ...message,
        isUser: message.from === currentUserId
    }
}

// Transform message from API response to match our interface
export function transformMessage(apiMessage: any, currentUserId: number): BackendMessage {
  return {
    id: apiMessage.id,
    user_id: apiMessage.receiver_id || apiMessage.user_id,
    from: apiMessage.sender_id || apiMessage.from,
    message: apiMessage.message,
    created_at: apiMessage.created_at,
    updated_at: apiMessage.updated_at,
    isUser: (apiMessage.sender_id || apiMessage.from) === currentUserId,
  }
}

// Utility function to sort messages by creation date
export function sortMessagesByDate(messages: BackendMessage[]): BackendMessage[] {
    return messages.sort((a, b) =>
        new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    )
}


// Utility function to format time for conversations
export function formatConversationTime(dateString: string): string {
    const date = new Date(dateString)
    const now = new Date()
    const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)
    const diffInDays = diffInHours / 24

    if (diffInHours < 1) {
        const diffInMinutes = Math.floor(diffInHours * 60)
        return diffInMinutes < 1 ? "now" : `${diffInMinutes}m`
    } else if (diffInHours < 24) {
        return `${Math.floor(diffInHours)}h`
    } else if (diffInDays < 7) {
        return `${Math.floor(diffInDays)}d`
    } else {
        return date.toLocaleDateString([], { month: "short", day: "numeric" })
    }
}

// Utility function to format message time
export function formatMessageTime(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)

  if (diffInHours < 24) {
    return date.toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    })
  } else {
    return date.toLocaleDateString([], {
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  }
}

// Utility function to cancel ongoing requests
export function cancelRequest(source: any) {
    if (source) {
        source.cancel('Request canceled by user')
    }
}

// Create cancel token source for requests
export function createCancelTokenSource() {
    return axios.CancelToken.source()
}

// NEW: Search users function
export async function searchUsers(query: string, authToken: string): Promise<any[]> {
  try {
    console.log("🔍 Searching users with query:", query)
    const response = await apiClient.get(`/api/v1/users/search?q=${encodeURIComponent(query)}`, {
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
    })

    console.log("📥 Search response:", response.data)

    if (!response.data.status) {
      throw new Error(response.data.message || "Failed to search users")
    }

    return response.data.data || []
  } catch (error) {
    if (axios.isAxiosError(error)) {
      console.error("Axios error searching users:", error.response?.data)
      throw new Error(error.response?.data?.message || "Failed to search users")
    }

    console.error("Unexpected error searching users:", error)
    return []
  }
}

// Export types
export type { Conversation, BackendMessage }
