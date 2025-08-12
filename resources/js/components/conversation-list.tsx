"use client"

import { useState, useEffect } from "react"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { ScrollArea } from "@/components/ui/scroll-area"
import { Skeleton } from "@/components/ui/skeleton"
import { Search, MessageCircle, RefreshCw } from "lucide-react"
import { fetchConversations, formatConversationTime } from '@/utils/chat-api'
import { Conversation } from '@/types/chat';

interface ConversationListProps {
    currentUserId: number
    onSelectConversation: (conversation: Conversation) => void
    selectedConversationId?: number
}

export function ConversationList({
                                     currentUserId,
                                     onSelectConversation,
                                     selectedConversationId,
                                 }: ConversationListProps) {
    const [conversations, setConversations] = useState<Conversation[]>([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState<string | null>(null)
    const [searchQuery, setSearchQuery] = useState("")

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

    const filteredConversations = conversations.filter(
        (conv) =>
            conv.user.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            conv.user.email.toLowerCase().includes(searchQuery.toLowerCase()) ||
            conv.last_message.toLowerCase().includes(searchQuery.toLowerCase()),
    )

    const getTotalUnreadCount = () => {
        return conversations.reduce((total, conv) => total + conv.unread_count, 0)
    }

    if (loading) {
        return (
            <div className="w-100 border-r border-zinc-800 bg-zinc-900 flex flex-col">
                <div className="p-4 border-b border-zinc-800">
                    <Skeleton className="h-10 w-full" />
                </div>
                <div className="flex-1 p-4 space-y-4">
                    {[...Array(5)].map((_, i) => (
                        <div key={i} className="flex items-center space-x-3">
                            <Skeleton className="h-12 w-12 rounded-full" />
                            <div className="space-y-2 flex-1">
                                <Skeleton className="h-4 w-3/4" />
                                <Skeleton className="h-3 w-1/2" />
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        )
    }

    return (
        <div className="w-100 border-r border-zinc-800 bg-zinc-900 flex flex-col">
            {/* Header */}
            <div className="p-4 border-b border-zinc-800">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-lg font-semibold text-white flex items-center gap-2">
                        <MessageCircle className="w-5 h-5" />
                        Messages
                        {getTotalUnreadCount() > 0 && (
                            <Badge variant="destructive" className="ml-2">
                                {getTotalUnreadCount()}
                            </Badge>
                        )}
                    </h2>
                    <Button variant="ghost" size="sm" onClick={loadConversations} disabled={loading}>
                        <RefreshCw className={`w-4 h-4 ${loading ? "animate-spin" : ""}`} />
                    </Button>
                </div>

                {/* Search */}
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-zinc-400 w-4 h-4" />
                    <Input
                        placeholder="Search conversations..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="pl-10 bg-zinc-800 border-zinc-700 text-white placeholder:text-zinc-500"
                    />
                </div>
            </div>

            {/* Error State */}
            {error && (
                <div className="p-4 bg-red-900/20 border-b border-red-800">
                    <p className="text-red-400 text-sm">{error}</p>
                    <Button variant="ghost" size="sm" onClick={loadConversations} className="mt-2 text-red-400">
                        Try Again
                    </Button>
                </div>
            )}

            {/* Conversations List */}
            <ScrollArea className="flex-1">
                {filteredConversations.length === 0 ? (
                    <div className="p-8 text-center text-zinc-500">
                        <MessageCircle className="w-12 h-12 mx-auto mb-4 opacity-50" />
                        <p className="text-sm">{searchQuery ? "No conversations found" : "No conversations yet"}</p>
                    </div>
                ) : (
                    <div className="p-2">
                        {filteredConversations.map((conversation) => (
                            <button
                                key={conversation.conversation_id}
                                onClick={() => onSelectConversation(conversation)}
                                className={`w-full p-3 rounded-lg text-left hover:bg-zinc-800 transition-colors ${
                                    selectedConversationId === conversation.conversation_id ? "bg-zinc-800 border border-zinc-700" : ""
                                }`}
                            >
                                <div className="flex items-center gap-3">
                                    <div className="relative">
                                        <Avatar className="w-12 h-12">
                                            <AvatarImage
                                                src={conversation.user.avatar || "/placeholder.svg?height=48&width=48"}
                                                alt={conversation.user.name}
                                            />
                                            <AvatarFallback className="bg-zinc-700 text-white">
                                                {conversation.user.name
                                                    .split(" ")
                                                    .map((n: any) => n[0])
                                                    .join("")
                                                    .toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                        {conversation.unread_count > 0 && (
                                            <div className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center">
                        <span className="text-xs text-white font-medium">
                          {conversation.unread_count > 9 ? "9+" : conversation.unread_count}
                        </span>
                                            </div>
                                        )}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between mb-1">
                                            <h3
                                                className={`font-medium truncate ${
                                                    conversation.unread_count > 0 ? "text-white" : "text-zinc-300"
                                                }`}
                                            >
                                                {conversation.user.name}
                                            </h3>
                                            <span className="text-xs text-zinc-500 flex-shrink-0">
                        {formatConversationTime(conversation.last_message_at)}
                      </span>
                                        </div>
                                        <p
                                            className={`text-sm truncate ${
                                                conversation.unread_count > 0 ? "text-zinc-300 font-medium" : "text-zinc-500"
                                            }`}
                                        >
                                            {conversation.last_message}
                                        </p>
                                    </div>
                                </div>
                            </button>
                        ))}
                    </div>
                )}
            </ScrollArea>
        </div>
    )
}
