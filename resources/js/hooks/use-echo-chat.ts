'use client'

import { useState, useEffect, useRef, useCallback } from 'react'
import axios from 'axios'
import { fetchMessages, sendMessageToBackend, determineIsUser, sortMessagesByDate, createCancelTokenSource } from '@/utils/chat-api'
import { setupEcho, createChannelName, convertLaravelMessage, LaravelMessage, TypingEvent } from '@/utils/echo-setup'

interface Message {
    id: number
    user_id: number
    from: number
    message: string
    created_at: string
    updated_at: string
    isUser: boolean
}

export function useEchoChat(currentUserId: number, chatUserId: number, authToken?: string) {
    const [messages, setMessages] = useState<Message[]>([])
    const [loading, setLoading] = useState(true)
    const [sending, setSending] = useState(false)
    const [error, setError] = useState<string | null>(null)
    const [isConnected, setIsConnected] = useState(false)
    const [isTyping, setIsTyping] = useState(false)
    const [otherUserTyping, setOtherUserTyping] = useState(false)
    const cancelTokenRef = useRef<any>(null)
    const echoRef = useRef<any>(null)
    const channelRef = useRef<any>(null)
    const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null)

    // Setup Echo connection
    useEffect(() => {
        if (typeof window === 'undefined') return

        try {
            const echo = setupEcho(authToken)
            if (echo) {
                echoRef.current = echo
                console.log('✅ Echo setup completed')
            }
        } catch (error) {
            console.error('❌ Failed to setup Echo:', error)
            setError('Failed to setup real-time connection')
        }

        return () => {
            // Cleanup Echo connection
            if (channelRef.current) {
                channelRef.current.stopListening('.MessageSent')
                channelRef.current.stopListening('.UserTyping')
                console.log('🔌 Disconnected from chat channel')
            }
            if (echoRef.current) {
                echoRef.current.disconnect()
            }
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current)
            }
        }
    }, [authToken])

    // Setup chat channel subscription
    useEffect(() => {
        if (!echoRef.current || !currentUserId || !chatUserId) return

        const channelName = createChannelName(currentUserId, chatUserId)
        console.log('🔄 Attempting to join chat channel:', channelName)

        try {
            const channel = echoRef.current.private(channelName)
            channelRef.current = channel

            channel
                .subscribed(() => {
                    console.log('✅ Successfully subscribed to chat channel:', channelName)
                    setIsConnected(true)
                    setError(null)
                })
                .listen('.MessageSent', (e: LaravelMessage) => {
                    console.log('📨 MessageSent received:', e)

                    // Only add message if it's NOT from the current user
                    // (to prevent duplicates since we already show optimistic updates)
                    if (e.message.sender_id !== currentUserId) {
                        const newMessage = convertLaravelMessage(e, currentUserId)

                        setMessages(prev => {
                            // Check if message already exists
                            const exists = prev.some(msg =>
                                (msg.id === newMessage.id && newMessage.id !== Date.now()) || // Check by ID if available
                                (msg.message === newMessage.message &&
                                    msg.from === newMessage.from &&
                                    Math.abs(new Date(msg.created_at).getTime() - new Date(newMessage.created_at).getTime()) < 2000)
                            )

                            if (exists) {
                                console.log('⚠️ Duplicate message detected, skipping')
                                return prev
                            }

                            console.log('✅ Adding new message from other user')
                            return sortMessagesByDate([...prev, newMessage])
                        })
                    } else {
                        console.log('📤 Ignoring own message to prevent duplicate')
                    }
                })
                .listen('.UserTyping', (e: TypingEvent) => {
                    console.log('⌨️ UserTyping event received:', e)

                    // Only show typing indicator for other users
                    if (e.user_id !== currentUserId) {
                        setOtherUserTyping(e.typing)

                        // Auto-hide typing indicator after 3 seconds
                        if (e.typing) {
                            if (typingTimeoutRef.current) {
                                clearTimeout(typingTimeoutRef.current)
                            }
                            typingTimeoutRef.current = setTimeout(() => {
                                setOtherUserTyping(false)
                            }, 3000)
                        }
                    }
                })
                .error((error: any) => {
                    console.error('❌ Error subscribing to chat channel:', error)
                    setError('Failed to connect to real-time chat')
                    setIsConnected(false)
                })

        } catch (error) {
            console.error('❌ Failed to setup chat channel:', error)
            setError('Failed to setup chat channel')
        }

        return () => {
            if (channelRef.current) {
                channelRef.current.stopListening('.MessageSent')
                channelRef.current.stopListening('.UserTyping')
                console.log('🔌 Stopped listening to chat events')
            }
        }
    }, [currentUserId, chatUserId])

  // Load initial messages from database
  useEffect(() => {
    loadMessages()

    return () => {
      if (cancelTokenRef.current) {
        cancelTokenRef.current.cancel('Component unmounted')
      }
    }
  }, [currentUserId, chatUserId]) // Add chatUserId to dependencies

  const loadMessages = async () => {
    setLoading(true)
    setError(null)

    if (cancelTokenRef.current) {
      cancelTokenRef.current.cancel('New request initiated')
    }

    cancelTokenRef.current = createCancelTokenSource()

    try {
      console.log('📥 Loading messages for user:', currentUserId)
      const fetchedMessages = await fetchMessages(currentUserId)
      console.log('📥 Fetched messages:', fetchedMessages.length)

      const messagesWithUserFlag = fetchedMessages.map(msg =>
        determineIsUser(msg, currentUserId)
      )
      const sortedMessages = sortMessagesByDate(messagesWithUserFlag)
      setMessages(sortedMessages)
      console.log('✅ Messages loaded and sorted')
    } catch (error) {
      if (axios.isCancel(error)) {
        console.log('Request canceled:', error.message)
        return
      }

      console.error('Failed to load messages:', error)
      setError(error instanceof Error ? error.message : 'Unable to load messages')
    } finally {
      setLoading(false)
    }
  }

  const sendTypingEvent = useCallback((typing: boolean) => {
    if (channelRef.current && isConnected) {
      // Send typing event to Laravel backend
      channelRef.current.whisper('typing', {
        user_id: currentUserId,
        typing: typing
      })
    }
  }, [currentUserId, isConnected])

  const handleTyping = useCallback(() => {
    if (!isTyping) {
      setIsTyping(true)
      sendTypingEvent(true)
    }

    // Clear existing timeout
    if (typingTimeoutRef.current) {
      clearTimeout(typingTimeoutRef.current)
    }

    // Set new timeout to stop typing
    typingTimeoutRef.current = setTimeout(() => {
      setIsTyping(false)
      sendTypingEvent(false)
    }, 1000)
  }, [isTyping, sendTypingEvent])

  const sendMessage = async (messageText: string) => {
    if (!messageText.trim() || sending) return

    setSending(true)
    setError(null)

    // Stop typing when sending message
    if (isTyping) {
      setIsTyping(false)
      sendTypingEvent(false)
    }

    // Optimistically add message to UI
    const optimisticMessage: Message = {
      id: Date.now(),
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
        // Replace optimistic message with real one from backend
        setMessages(prev =>
          prev.map(msg =>
            msg.id === optimisticMessage.id ? messageWithUserFlag : msg
          )
        )
        console.log('✅ Message sent and replaced with backend response')
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
      setMessages(prev => prev.filter(msg => msg.id !== optimisticMessage.id))
      setError(error instanceof Error ? error.message : 'Failed to send message')
    } finally {
      setSending(false)
    }
  }

    const reconnect = useCallback(() => {
        if (echoRef.current) {
            echoRef.current.disconnect()
            const echo = setupEcho(authToken)
            if (echo) {
                echoRef.current = echo
                setIsConnected(false)
            }
        }
    }, [authToken])

    return {
        messages,
        loading,
        sending,
        error,
        isConnected,
        otherUserTyping,
        sendMessage,
        refreshMessages: loadMessages,
        reconnect,
        handleTyping
    }
}
