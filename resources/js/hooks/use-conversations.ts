"use client"

import { useState, useEffect, useCallback } from "react"
import { fetchConversations, markConversationAsRead, type Conversation } from '@/utils/chat-api'

export function useConversations(currentUserId: number) {
    const [conversations, setConversations] = useState<Conversation[]>([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState<string | null>(null)

    useEffect(() => {
        loadConversations()
    }, [currentUserId])

    const loadConversations = async () => {
        setLoading(true)
        setError(null)

        try {
            const fetchedConversations = await fetchConversations(currentUserId)
            setConversations(fetchedConversations)
        } catch (error) {
            console.error("Failed to load conversations:", error)
            setError(error instanceof Error ? error.message : "Failed to load conversations")
        } finally {
            setLoading(false)
        }
    }

    const markAsRead = useCallback(async (conversationId: number) => {
        const success = await markConversationAsRead(conversationId)
        if (success) {
            setConversations((prev) =>
                prev.map((conv) => (conv.conversation_id === conversationId ? { ...conv, unread_count: 0 } : conv)),
            )
        }
        return success
    }, [])

    const updateLastMessage = useCallback((conversationId: number, message: string, timestamp: string) => {
        setConversations((prev) =>
            prev.map((conv) =>
                conv.conversation_id === conversationId ? { ...conv, last_message: message, last_message_at: timestamp } : conv,
            ),
        )
    }, [])

    const incrementUnreadCount = useCallback((conversationId: number) => {
        setConversations((prev) =>
            prev.map((conv) =>
                conv.conversation_id === conversationId ? { ...conv, unread_count: conv.unread_count + 1 } : conv,
            ),
        )
    }, [])

    const getTotalUnreadCount = useCallback(() => {
        return conversations.reduce((total, conv) => total + conv.unread_count, 0)
    }, [conversations])

    return {
        conversations,
        loading,
        error,
        loadConversations,
        markAsRead,
        updateLastMessage,
        incrementUnreadCount,
        getTotalUnreadCount,
    }
}
