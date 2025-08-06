"use client"

import { useState } from "react"
import { Plus, ArrowUp } from 'lucide-react'
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { User } from '@/types';

interface Message {
    id: number
    text: string
    isUser: boolean
}

type ChatInterfaceProps = {
    user: User | null;
    onSendMessage: (receiver: User, message: string) => void;
};

export const ChatInterface = ({ user, onSendMessage }: ChatInterfaceProps) => {
    if (!user) return null;

    const [messages, setMessages] = useState<Message[]>([
        { id: 1, text: "Hi, how can I help you today?", isUser: false },
        { id: 2, text: "Hey, I'm having trouble with my account.", isUser: true },
        { id: 3, text: "What seems to be the problem?", isUser: false },
        { id: 4, text: "I can't log in.", isUser: true },
    ])
    const [inputValue, setInputValue] = useState("")


    const handleSendMessage = () => {
        if (inputValue.trim()) {
            // const newMessage: Message = {
            //     id: messages.length + 1,
            //     text: inputValue,
            //     isUser: true,
            // }
            onSendMessage(user, inputValue);
            // setMessages([...messages, newMessage])
            setInputValue("")
        }
    }

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === "Enter") {
            handleSendMessage()
        }
    }

    return (
        <>
            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div className="flex items-center gap-3">
                    <Avatar className="w-12 h-12">
                        <AvatarImage src="/professional-woman-avatar.png" alt="Sofia Davis" />
                        <AvatarFallback className="bg-white text-black">SD</AvatarFallback>
                    </Avatar>
                    <div>
                        <h2 className="font-semibold text-lg">Chat with {user.name}</h2>
                        <p className="text-zinc-400 text-sm">{user.email}</p>
                    </div>
                </div>
                <Button
                    size="icon"
                    variant="ghost"
                    className="rounded-full bg-zinc-800 hover:bg-zinc-700 w-10 h-10"
                >
                    <Plus className="w-5 h-5" />
                </Button>
            </div>

            {/* Messages */}
            <div className="flex-1 space-y-4 mb-6">
                {messages.map((message) => (
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
                            <p className="text-sm leading-relaxed">{message.text}</p>
                        </div>
                    </div>
                ))}
            </div>

            {/* Input */}
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
                    className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-zinc-700 hover:bg-zinc-600 w-8 h-8"
                >
                    <ArrowUp className="w-4 h-4" />
                </Button>
            </div>
        </>
    )
}
