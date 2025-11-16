'use client';

import { debounce } from 'lodash';
import { useState, useEffect, useRef, useCallback } from 'react';
import axios from 'axios';
import {
    fetchMessagesByConversation,
    sendMessageToBackend,
    getOrCreateConversation,
    transformMessage,
    sortMessagesByDate,
    createCancelTokenSource,
    sendTypingEventToBackend
} from '@/utils/chat-api';
import { setupEcho, createChannelName } from '@/utils/echo-setup';
import { BackendMessage, MessageSentEventResponse, TypingEventResponse } from '@/types/chat';

export function useEchoChat(
    currentUserId: number,
    chatUserId: number, // This should be the OTHER user's ID, not the current user
    authToken?: string,
    initialConversationId?: number,
) {
    console.log("useEchoChat initialized with:", {
        currentUserId,
        chatUserId,
        initialConversationId,
        channelWillBe: `chat.${Math.min(currentUserId, chatUserId)}-${Math.max(currentUserId, chatUserId)}`,
    })
    const [messages, setMessages] = useState<BackendMessage[]>([]);
    const [loading, setLoading] = useState(true);
    const [channelName, setChannelName] = useState<string | null>(null);
    const [sending, setSending] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [isConnected, setIsConnected] = useState(false);
    const [isTyping, setIsTyping] = useState(false);
    const [otherUserTyping, setOtherUserTyping] = useState(false);
    const [conversationId, setConversationId] = useState<number | null>(initialConversationId || null)
    const cancelTokenRef = useRef<any>(null);
    const echoRef = useRef<any>(null);
    const channelRef = useRef<any>(null);
    const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    // Setup Echo connection
    useEffect(() => {
        if (typeof window === 'undefined') return;

        try {
            const echo = setupEcho(authToken);
            if (echo) {
                echoRef.current = echo;
                console.log('✅ Echo setup completed');
            }
        } catch (error) {
            console.error('❌ Failed to setup Echo:', error);
            setError('Failed to setup real-time connection');
        }

        return () => {
            // Cleanup Echo connection
            if (channelRef.current) {
                channelRef.current.stopListening('.MessageSent');
                channelRef.current.stopListening('.UserTyping');
                console.log('🔌 Disconnected from chat channel');
            }
            if (echoRef.current) {
                echoRef.current.disconnect();
            }
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
            }
        };
    }, [authToken]);

  // Setup chat channel subscription
  useEffect(() => {
    if (!echoRef.current || !currentUserId || !chatUserId || currentUserId === chatUserId) {
      console.warn("⚠️ Invalid user IDs for chat channel:", { currentUserId, chatUserId })
      return
    }

        const channelName = createChannelName(currentUserId, chatUserId);
        // const groupChannelName = `chat.conversation.${conversationId}`;
        setChannelName(channelName);
        console.log('🔄 Attempting to join chat channel:', channelName);
        console.log("📋 Channel participants:", { currentUserId, chatUserId })

        try {
            const channel = echoRef.current.private(channelName);
            channelRef.current = channel;
            channel
                .subscribed(() => {
                    console.log('✅ Successfully subscribed to chat channel:', channelName)
                    console.log("👥 Channel participants:", { currentUserId, otherUserId: chatUserId })
                    setIsConnected(true);
                    setError(null);
                })
                .listen('.MessageSent', (e: MessageSentEventResponse) => {
                    console.log('📨 MessageSent received:', e);
                    console.log("🔍 Message sender:", e.message.sender_id, "Current user:", currentUserId)

                    // Only add message if it's NOT from the current user
                    // (to prevent duplicates since we already show optimistic updates)
                    if (e.message.sender_id !== currentUserId) {
                        const newMessage = convertMessageSentEventResponse(e, currentUserId)
                        console.log("✅ Adding message from other user:", newMessage)

                        setMessages(prev => {
                            // Check if message already exists
                            const exists = prev.some(msg =>
                                (msg.id === newMessage.id && newMessage.id !== Date.now()) || // Check by ID if available
                                (msg.message === newMessage.message &&
                                    msg.from === newMessage.from &&
                                    Math.abs(new Date(msg.created_at).getTime() - new Date(newMessage.created_at).getTime()) < 2000)
                            );

                            if (exists) {
                                console.log('⚠️ Duplicate message detected, skipping');
                                return prev;
                            }

                            console.log('✅ Adding new message from other user');
                            return sortMessagesByDate([...prev, newMessage]);
                        });
                    } else {
                        console.log('📤 Ignoring own message to prevent duplicate');
                    }
                })
                .listen('.UserTyping', (e: TypingEventResponse) => {
                    console.log('⌨️ UserTyping event received:', e);
                    console.log("🔍 Typing user:", e.user_id, "Current user:", currentUserId)

                    // Only show typing indicator for other users
                    if (e.user_id !== currentUserId) {
                        setOtherUserTyping(e.typing);

                        // Auto-hide typing indicator after 3 seconds
                        if (e.typing) {
                            if (typingTimeoutRef.current) {
                                clearTimeout(typingTimeoutRef.current);
                            }
                            typingTimeoutRef.current = setTimeout(() => {
                                setOtherUserTyping(false);
                            }, 3000);
                        }
                    }
                })
                .error((error: any) => {
                    console.error('❌ Error subscribing to chat channel:', error);
                    setError('Failed to connect to real-time chat');
                    setIsConnected(false);
                });

        } catch (error) {
            console.error('❌ Failed to setup chat channel:', error);
            setError('Failed to setup chat channel');
        }

        return () => {
            if (channelRef.current) {
                channelRef.current.stopListening('.MessageSent');
                channelRef.current.stopListening('.UserTyping');
                console.log('🔌 Stopped listening to chat events');
            }
        };
    }, [currentUserId, chatUserId]);

  // Initialize conversation and load messages
  useEffect(() => {
      if (initialConversationId) {
          setConversationId(initialConversationId)
          loadMessages(initialConversationId)
      } else if (currentUserId && chatUserId) {
          initializeConversation()
      }

        return () => {
            if (cancelTokenRef.current) {
                cancelTokenRef.current.cancel('Component unmounted');
            }
        };
      }, [currentUserId, chatUserId, initialConversationId]) // Add chatUserId to dependencies

    const initializeConversation = async () => {
        setLoading(true)
        setError(null)

        try {
            // Get or create conversation
            console.log("🔄 Initializing conversation...")
            const conversation = await getOrCreateConversation(currentUserId, chatUserId)
            setConversationId(conversation.id)
            console.log("✅ Conversation ID:", conversation.id)

            // Load messages for this conversation
            await loadMessages(conversation.id)
        } catch (error) {
            console.error("❌ Failed to initialize conversation:", error)
            setError(error instanceof Error ? error.message : "Failed to initialize conversation")
        } finally {
            setLoading(false)
        }
    }

    const loadMessages = async (convId?: number) => {
        console.log("Loading messages for conversation:", convId)
        const targetConversationId = convId || conversationId
        if (!targetConversationId) {
            console.warn("⚠️ No conversation ID available for loading messages")
            setLoading(false)
            return
        }

        if (cancelTokenRef.current) {
            cancelTokenRef.current.cancel('New request initiated');
        }

        cancelTokenRef.current = createCancelTokenSource();

        try {
            console.log("📥 Loading messages for conversation:", targetConversationId)
            const fetchedMessages = await fetchMessagesByConversation(targetConversationId)
            console.log('📥 Fetched messages:', fetchedMessages.length);

      // Transform messages to match our interface
      const transformedMessages = fetchedMessages.map((msg) => transformMessage(msg, currentUserId))
      const sortedMessages = sortMessagesByDate(transformedMessages)
      setMessages(sortedMessages)
      console.log("✅ Messages loaded and sorted")
    } catch (error) {
      if (axios.isCancel(error)) {
        console.log("Request canceled:", error.message)
        return
      }

      console.error("Failed to load messages:", error)
      setError(error instanceof Error ? error.message : "Unable to load messages")
    } finally {
      setLoading(false)
    }
  }

    // Enhanced sendTypingEvent with better error handling
    const sendTypingEvent = useCallback(
        debounce(async (typing: boolean) => {
            if (channelRef.current && isConnected && currentUserId) {
                try {
                    // Send to backend first
                    const success = await sendTypingEventToBackend({
                        user_id: currentUserId,
                        channel: channelName,
                        typing: typing
                    });

                    // Only send real-time event if backend call succeeded
                    if (success) {
                        channelRef.current.whisper('typing', {
                            user_id: currentUserId,
                            typing: typing
                        });
                    }
                } catch (error) {
                    console.error('Failed to send typing event:', error);
                    // Optionally still send the real-time event even if backend fails
                    channelRef.current.whisper('typing', {
                        user_id: currentUserId,
                        typing: typing
                    });
                }
            }
        }, 300),
        [currentUserId, isConnected, channelName]
    );

    // Add proper cleanup and error handling
    const handleTyping = useCallback(() => {
        if (!isTyping) {
            setIsTyping(true);
            sendTypingEvent(true);
        }

        // Clear existing timeout
        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }

        // Set new timeout to stop typing
        typingTimeoutRef.current = setTimeout(() => {
            setIsTyping(false);
            sendTypingEvent(false);
        }, 1000);
    }, [isTyping, sendTypingEvent]);

    useEffect(() => {
        return () => {
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
            }
            // Send final typing stop event
            if (isTyping) {
                sendTypingEvent(false);
            }
        };
    }, [isTyping, sendTypingEvent]);

    const sendMessage = async (messageText: string) => {
        if (!messageText.trim() || sending || !conversationId) return;

        setSending(true);
        setError(null);

        // Stop typing when sending message
        if (isTyping) {
            setIsTyping(false);
            sendTypingEvent(false);
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
            }
        }

        // Optimistically add message to UI
        const optimisticMessage: BackendMessage = {
            id: Date.now(),
            user_id: chatUserId,
            from: currentUserId,
            message: messageText.trim(),
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            isUser: true
        };

        console.log('Optimistic Messages: ' + optimisticMessage);
        setMessages(prev => [...prev, optimisticMessage]);

        try {
            const payload = {
                conversation_id: conversationId,
                user_id: chatUserId,
                from: currentUserId,
                message: messageText.trim()
            };

      const newMessage = await sendMessageToBackend(payload)
      if (newMessage) {
        const messageWithUserFlag = transformMessage(newMessage, currentUserId)
        // Replace optimistic message with real one from backend
        setMessages((prev) => prev.map((msg) => (msg.id === optimisticMessage.id ? messageWithUserFlag : msg)))
        console.log("✅ Message sent and replaced with backend response")
      } else {
        // Remove optimistic message on failure
        setMessages((prev) => prev.filter((msg) => msg.id !== optimisticMessage.id))
        setError("Failed to send message")
      }
    } catch (error) {
      if (axios.isCancel(error)) {
        console.log("Send message request canceled:", error.message)
        return
      }

            console.error('Failed to send message:', error);
            setMessages(prev => prev.filter(msg => msg.id !== optimisticMessage.id));
            setError(error instanceof Error ? error.message : 'Failed to send message');
        } finally {
            setSending(false);
        }
    };

    // Convert Laravel message to our frontend format
    function convertMessageSentEventResponse(
        laravelMsg: MessageSentEventResponse,
        currentUserId: number
    ): BackendMessage {
        return {
            id: laravelMsg.message.id || Date.now(),
            user_id: laravelMsg.message.receiver_id,
            from: laravelMsg.message.sender_id,
            message: laravelMsg.message.message,
            created_at: laravelMsg.message.created_at,
            updated_at: laravelMsg.message.updated_at,
            isUser: laravelMsg.message.sender_id === currentUserId,
        }
    }

    const reconnect = useCallback(() => {
        if (echoRef.current) {
            echoRef.current.disconnect();
            const echo = setupEcho(authToken);
            if (echo) {
                echoRef.current = echo;
                setIsConnected(false);
            }
        }
    }, [authToken]);

    return {
        messages,
        loading,
        sending,
        error,
        isConnected,
        otherUserTyping,
        conversationId,
        sendMessage,
        refreshMessages: () => loadMessages(),
        reconnect,
        handleTyping
    };
}
