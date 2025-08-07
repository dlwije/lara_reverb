"use client"

import React, { useEffect, useRef, useState } from 'react';
import { Plus, ArrowUp } from 'lucide-react'
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { User } from '@/types';

interface User {
    id: string
    name: string
    email: string
    avatar?: string
}

interface Message {
    id: number
    user_id: number
    from: number
    message: string
    created_at: string
    updated_at: string
    isUser: boolean
}
interface ChatInterfaceProps {
    user: User | null
    messages: Message[]
    onSendMessage: (message: string) => void
}

export const ChatInterface = ({ user, messages, onSendMessage }: ChatInterfaceProps) => {

    const [inputValue, setInputValue] = useState('')
    const messagesEndRef = useRef<HTMLDivElement>(null)
    const messagesContainerRef = useRef<HTMLDivElement>(null)

    // Auto-scroll to bottom when messages change
    useEffect(() => {
        if (messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({
                behavior: 'smooth',
                block: 'end'
            })
        }
    }, [messages])

    const handleSendMessage = () => {
        if (inputValue.trim()) {
            onSendMessage(inputValue.trim())
            setInputValue('')
        }
    }

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault()
            handleSendMessage()
        }
    }

    return (
        <div className="flex flex-col h-full">
            {/* Messages Container - Scrollable */}
            <div
                ref={messagesContainerRef}
                className="flex-1 overflow-y-auto p-6 space-y-4"
                style={{ maxHeight: 'calc(100vh - 200px)' }}
            >
                {messages?.map((message) => (
                    <div
                        key={message.id}
                        className={`flex ${message.isUser ? "justify-end" : "justify-start"}`}
                    >
                        <div
                            className={`max-w-[80%] px-4 py-3 rounded-2xl ${
                                message.isUser
                                    ? "bg-zinc-300 text-zinc-900 rounded-br-md"
                                    : "bg-zinc-800 text-white rounded-bl-md"
                            }`}
                        >
                            <p className="text-sm leading-relaxed">{message.message}</p>
                            <p className="text-xs opacity-60 mt-1">
                                {new Date(message.created_at).toLocaleTimeString([], {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}
                            </p>
                        </div>
                    </div>
                ))}
                {/* Invisible element to scroll to */}
                <div ref={messagesEndRef} />
            </div>

            {/* Input Area - Fixed at bottom */}
            <div className="p-6 border-t border-zinc-800">
                <div className="relative">
                    <Input
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        onKeyPress={handleKeyPress}
                        placeholder="Type your message..."
                        className="w-full bg-zinc-800 border-zinc-700 rounded-2xl px-4 py-3 pr-12 text-white placeholder:text-zinc-500 focus:ring-2 focus:ring-zinc-600 focus:border-transparent"
                    />
                    <Button
                        onClick={handleSendMessage}
                        size="icon"
                        disabled={!inputValue.trim()}
                        className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-zinc-700 hover:bg-zinc-600 disabled:opacity-50 w-8 h-8"
                    >
                        <ArrowUp className="w-4 h-4" />
                    </Button>
                </div>
            </div>
        </div>
    )
}
