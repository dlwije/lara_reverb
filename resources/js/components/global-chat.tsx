'use client';

import { SheetDescription } from '@/components/ui/sheet';
import React, { useRef } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { useConversations } from '@/hooks/use-conversations';
import { useEchoChat } from '@/hooks/use-echo-chat';
import type { Conversation } from '@/types/chat';
import { VisuallyHidden } from '@radix-ui/react-visually-hidden';
import { AlertCircle, ArrowLeft, MessageCircle, Radio, WifiOff } from 'lucide-react';
import { useEffect, useState, useCallback } from 'react';
import { ChatInterface } from './chat-interface';
import { ConversationList } from './conversation-list';

interface GlobalChatProps {
    currentUser: {
        id: number;
        name: string;
        email: string;
        avatar?: string | null;
    };
    authToken: string;
    trigger?: React.ReactNode;
    className?: string;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

export function GlobalChat({ currentUser, authToken, trigger, className, open: externalOpen, onOpenChange: externalOnOpenChange }: GlobalChatProps) {
    const [internalOpen, setInternalOpen] = useState(false);
    const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null);
    const [showConversationList, setShowConversationList] = useState(true);

    const open = externalOpen !== undefined ? externalOpen : internalOpen;
    // ✅ Only initialize chat hook when conversation is selected AND chat is open
    const shouldInitializeChat = open && selectedConversation && selectedConversation.user.id;
    const setOpen = externalOnOpenChange || setInternalOpen;

    // Log prop changes
    const prevProps = useRef({ currentUser, authToken, externalOpen });
    useEffect(() => {
        console.log('🔍 GlobalChat props changed:', {
            currentUserChanged: prevProps.current.currentUser?.id !== currentUser?.id,
            authTokenChanged: prevProps.current.authToken !== authToken,
            openChanged: prevProps.current.externalOpen !== externalOpen,
            timestamp: new Date().toISOString()
        });
        prevProps.current = { currentUser, authToken, externalOpen };
    });


    // Use conversations hook
    const {
        conversations,
        loading: conversationsLoading,
        error: conversationsError,
        loadConversations,
        markAsRead,
        updateLastMessage,
        incrementUnreadCount,
        getTotalUnreadCount,
    } = useConversations(currentUser.id);


    // Log conversations hook changes
    useEffect(() => {
        console.log('💬 Conversations hook state:', {
            conversationsCount: conversations.length,
            loading: conversationsLoading,
            error: conversationsError,
            timestamp: new Date().toISOString()
        });
    }, [conversations.length, conversationsLoading, conversationsError]);

    // Use chat hook only when conversation is selected
    const {
        messages,
        loading: chatLoading,
        sending,
        error: chatError,
        isConnected,
        otherUserTyping,
        conversationId,
        sendMessage,
        refreshMessages,
        reconnect,
        handleTyping,
    } = useEchoChat(
        shouldInitializeChat ? currentUser.id : 0,
        shouldInitializeChat ? selectedConversation.user.id : 0,
        shouldInitializeChat ? authToken : undefined,
        shouldInitializeChat ? selectedConversation.conversation_id : undefined
    );

    // Log when useEchoChat is called unnecessarily
    useEffect(() => {
        if (!shouldInitializeChat) {
            console.log('🚫 useEchoChat called unnecessarily - no selected conversation');
        }
    }, [shouldInitializeChat]);
    // Reset conversation selection when chat opens
    useEffect(() => {
        if (open) {
            setSelectedConversation(null);
            setShowConversationList(true);
        }
    }, [open]);

    // Add debugging to see why this is re-rendering
    useEffect(() => {
        console.log('🔍 GlobalChat re-rendered:', {
            open,
            selectedConversation: selectedConversation?.conversation_id,
            shouldInitializeChat,
            timestamp: new Date().toISOString()
        });
    });

    // Fix: Properly handle conversation selection with read status update
    const handleSelectConversation = useCallback(async (conversation: Conversation) => {
        console.log('🔄 Selecting conversation:', conversation);
        setSelectedConversation(conversation);
        setShowConversationList(false);

        // Mark conversation as read if it has unread messages
        if (conversation.unread_count > 0) {
            try {
                await markAsRead(conversation.conversation_id);
                console.log('✅ Conversation marked as read:', conversation.conversation_id);

                // Force refresh conversations to update the unread count
                await loadConversations();
            } catch (error) {
                console.error('❌ Failed to mark conversation as read:', error);
            }
        }
    }, [markAsRead, loadConversations]);

    const handleBackToList = useCallback(() => {
        setSelectedConversation(null);
        setShowConversationList(true);
    }, []);

    const getConnectionStatus = () => {
        if (isConnected) {
            return (
                <div className="flex items-center gap-2 text-green-400">
                    <Radio className="h-4 w-4" />
                    <span className="text-xs">Live</span>
                </div>
            );
        }
        return (
            <div className="flex items-center gap-2 text-yellow-400">
                <WifiOff className="h-4 w-4" />
                <span className="text-xs">Offline</span>
            </div>
        );
    };

    const getErrorIcon = () => {
        if (chatError?.includes('timeout') || chatError?.includes('connection')) {
            return <WifiOff className="h-4 w-4" />;
        }
        return <AlertCircle className="h-4 w-4" />;
    };

    const totalUnreadCount = getTotalUnreadCount();

    // Default trigger if none provided
    const defaultTrigger = (
        <Button variant="ghost" size="sm" className={`relative ${className}`}>
            <MessageCircle className="h-5 w-5" />
            {totalUnreadCount > 0 && (
                <Badge variant="destructive" className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center p-0 text-xs">
                    {totalUnreadCount > 9 ? '9+' : totalUnreadCount}
                </Badge>
            )}
        </Button>
    );

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>{trigger || defaultTrigger}</SheetTrigger>
            <SheetTitle></SheetTitle>
            <SheetContent className="flex h-full w-full max-w-4xl flex-col border-zinc-800 bg-zinc-900 p-0 text-white">
                {/* Show conversation list when no conversation is selected OR on desktop */}
                <div
                    className={`${
                        selectedConversation && !showConversationList ? 'hidden lg:flex' : 'flex'
                    } ${selectedConversation ? 'lg:w-80 lg:flex-shrink-0' : 'flex-1'}`}
                >
                    <ConversationList
                        currentUserId={currentUser.id}
                        onSelectConversation={handleSelectConversation}
                        selectedConversationId={selectedConversation?.conversation_id}
                    />
                </div>

                {/* Show chat interface when conversation is selected */}
                {selectedConversation && (
                    <div className={`${showConversationList ? 'hidden lg:flex' : 'flex'} min-h-0 flex-1 flex-col`}>
                        <SheetHeader className="flex-shrink-0 border-b border-zinc-800 p-6">
                            <div className="flex items-center gap-3">
                                {/* FIX: Show back arrow on all screen sizes when conversation is selected */}
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleBackToList}
                                    className="text-zinc-400 hover:text-white"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                </Button>
                                <Avatar className="h-12 w-12">
                                    <AvatarImage
                                        src={selectedConversation.user.avatar || '/placeholder.svg'}
                                        alt={selectedConversation.user.name}
                                    />
                                    <AvatarFallback className="bg-white text-black">
                                        {selectedConversation.user.name
                                            .split(' ')
                                            .map((n) => n[0])
                                            .join('')}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="min-w-0 flex-1">
                                    <h2 className="truncate text-lg font-semibold">Chat with {selectedConversation.user.name}</h2>
                                    <p className="truncate text-sm text-zinc-400">
                                        {selectedConversation.user.email}
                                        {conversationId && <span className="ml-2">• Conv: {conversationId}</span>}
                                        {otherUserTyping && <span className="ml-2 text-green-400">• typing...</span>}
                                    </p>
                                </div>
                                <div className="flex flex-shrink-0 items-center gap-2">
                                    {getConnectionStatus()}
                                </div>
                            </div>
                        </SheetHeader>

                        <VisuallyHidden>
                            <SheetDescription>Chat interface for selected conversation</SheetDescription>
                        </VisuallyHidden>

                        {chatError && (
                            <div className="flex-shrink-0 border-b border-red-800 bg-red-900/20 p-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2 text-red-400">
                                        {getErrorIcon()}
                                        <span className="text-sm">{chatError}</span>
                                    </div>
                                    {!isConnected && (
                                        <Button variant="ghost" size="sm" onClick={reconnect} className="text-red-400 hover:text-red-300">
                                            Reconnect
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}

                        {chatLoading ? (
                            <div className="flex flex-shrink-0 items-center space-x-4 p-6">
                                <Skeleton className="h-12 w-12 rounded-full" />
                                <div className="space-y-2">
                                    <Skeleton className="h-4 w-[250px]" />
                                    <Skeleton className="h-4 w-[200px]" />
                                </div>
                            </div>
                        ) : (
                            <div className="min-h-0 flex-1">
                                <ChatInterface
                                    user={{
                                        id: selectedConversation.user.id.toString(), // FIX: Use user instead of user2
                                        name: selectedConversation.user.name,
                                        email: selectedConversation.user.email,
                                        avatar: selectedConversation.user.avatar || undefined,
                                    }}
                                    messages={messages}
                                    onSendMessage={sendMessage}
                                    sending={sending}
                                    isConnected={isConnected}
                                    otherUserTyping={otherUserTyping}
                                    onTyping={handleTyping}
                                />
                            </div>
                        )}
                    </div>
                )}

                {/* Empty State - only show when no conversation is selected and conversation list is hidden */}
                {!selectedConversation && !showConversationList && (
                    <div className="flex flex-1 items-center justify-center text-zinc-500">
                        <div className="text-center">
                            <MessageCircle className="mx-auto mb-4 h-16 w-16 opacity-50" />
                            <p>Select a conversation to start chatting</p>
                        </div>
                    </div>
                )}
            </SheetContent>
        </Sheet>
    );
}
