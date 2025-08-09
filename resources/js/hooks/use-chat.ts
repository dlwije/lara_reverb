'use client'

import { useState, useEffect, useRef } from 'react'
import axios from 'axios'
import { fetchMessages, sendMessageToBackend, determineIsUser, sortMessagesByDate, createCancelTokenSource } from '@/utils/chat-api'

interface Message {
    id: number
    user_id: number
    from: number
    message: string
    created_at: string
    updated_at: string
    isUser: boolean
}

export function useChat(currentUserId: number, chatUserId: number) {
    const [messages, setMessages] = useState<Message[]>([])
    const [loading, setLoading] = useState(true)
    const [sending, setSending] = useState(false)
    const [error, setError] = useState<string | null>(null)
    const cancelTokenRef = useRef<any>(null)

    // Load messages on mount
    useEffect(() => {
        loadMessages()

        // Cleanup function to cancel requests
        return () => {
            if (cancelTokenRef.current) {
                cancelTokenRef.current.cancel('Component unmounted')
            }
        }
    }, [currentUserId])

    const loadMessages = async () => {
        setLoading(true)
        setError(null)

        // Cancel previous request if exists
        if (cancelTokenRef.current) {
            cancelTokenRef.current.cancel('New request initiated')
        }

        // Create new cancel token
        cancelTokenRef.current = createCancelTokenSource()

        try {
            const fetchedMessages = await fetchMessages(currentUserId)
            const messagesWithUserFlag = fetchedMessages.map(msg =>
                determineIsUser(msg, currentUserId)
            )
            const sortedMessages = sortMessagesByDate(messagesWithUserFlag)
            setMessages(sortedMessages)
        } catch (error) {
            if (axios.isCancel(error)) {
                console.log('Request canceled:', error.message)
                return
            }

            console.error('Failed to load messages:', error)
            setError(error instanceof Error ? error.message : 'Unable to load messages. Please check your connection.')
        } finally {
            setLoading(false)
        }
    }

    const sendMessage = async (messageText: string) => {
        if (!messageText.trim() || sending) return

        setSending(true)
        setError(null)

        // Optimistically add message to UI
        const optimisticMessage: Message = {
            id: Date.now(), // Temporary ID
            user_id: chatUserId,
            from: currentUserId,
            message: messageText.trim(),
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            isUser: true
        }

        setMessages(prev => [...prev, optimisticMessage])

        try {
            const payload = {
                user_id: chatUserId,
                from: currentUserId,
                message: messageText.trim()
            }

            const newMessage = await sendMessageToBackend(payload)
            if (newMessage) {
                const messageWithUserFlag = determineIsUser(newMessage, currentUserId)
                // Replace optimistic message with real one
                setMessages(prev =>
                    prev.map(msg =>
                        msg.id === optimisticMessage.id ? messageWithUserFlag : msg
                    )
                )
            } else {
                // Remove optimistic message on failure
                setMessages(prev => prev.filter(msg => msg.id !== optimisticMessage.id))
                setError('Failed to send message')
            }
        } catch (error) {
            if (axios.isCancel(error)) {
                console.log('Send message request canceled:', error.message)
                return
            }

            console.error('Failed to send message:', error)
            // Remove optimistic message on failure
            setMessages(prev => prev.filter(msg => msg.id !== optimisticMessage.id))
            setError(error instanceof Error ? error.message : 'Failed to send message')
        } finally {
            setSending(false)
        }
    }

    return {
        messages,
        loading,
        sending,
        error,
        sendMessage,
        refreshMessages: loadMessages
    }
}
