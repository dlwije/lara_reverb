'use client'

import React, { useEffect, useRef, useState } from 'react';
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
// import { User } from '@/types';
import { ArrowUp, Loader2, Radio } from 'lucide-react'

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
  sending?: boolean
  isConnected?: boolean
  otherUserTyping?: boolean
  onTyping?: () => void
}

export function ChatInterface({
  user,
  messages,
  onSendMessage,
  sending = false,
  isConnected = false,
  otherUserTyping = false,
  onTyping
}: ChatInterfaceProps) {
  const [inputValue, setInputValue] = useState('')
  const messagesEndRef = useRef<HTMLDivElement>(null)
  const messagesContainerRef = useRef<HTMLDivElement>(null)

    console.log('otherUserTyping ChatInterface: ', otherUserTyping)
  // Auto-scroll to bottom when messages change or when typing indicator appears
  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({
        behavior: 'smooth',
        block: 'end'
      })
    }
  }, [messages, otherUserTyping])

  const handleSendMessage = () => {
    if (inputValue.trim() && !sending) {
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

// Enhanced input change handler
    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value
        setInputValue(value)

        // Only trigger typing if there's actual content
        if (value.trim().length > 0 && onTyping) {
            onTyping()
        }
        // else if (value.trim().length === 0 && isTyping) {
        //     // Stop typing immediately if input is cleared
        //     setIsTyping(false)
        //     sendTypingEvent(false)
        //     if (typingTimeoutRef.current) {
        //         clearTimeout(typingTimeoutRef.current)
        //     }
        // }
    }

    const formatTime = (dateString: string) => {
        const date = new Date(dateString)
        const now = new Date()
        const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)

        if (diffInHours < 24) {
            return date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            })
        } else {
            return date.toLocaleDateString([], {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        }
    }

  const TypingIndicator = () => (
    <div className="flex justify-start mb-4">
      <div className="bg-zinc-800 text-white rounded-2xl rounded-bl-md px-4 py-3 max-w-[80%]">
        <div className="flex items-center space-x-1">
          <div className="flex space-x-1">
            <div className="w-2 h-2 bg-zinc-400 rounded-full animate-bounce"></div>
            <div className="w-2 h-2 bg-zinc-400 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }}></div>
            <div className="w-2 h-2 bg-zinc-400 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
          </div>
          <span className="text-xs text-zinc-400 ml-2">{user?.name} is typing...</span>
        </div>
      </div>
    </div>
  )

  return (
    <div className="flex flex-col h-full">
      {/* Messages Container - Scrollable */}
      <div
        ref={messagesContainerRef}
        className="flex-1 overflow-y-auto p-6 space-y-4"
        style={{ maxHeight: 'calc(100vh - 200px)' }}
      >
        {messages.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-full text-zinc-500 space-y-2">
            <p>No messages yet. Start the conversation!</p>
            {isConnected && (
              <div className="flex items-center gap-2 text-green-400 text-sm">
                <Radio className="w-3 h-3" />
                <span>Real-time updates active</span>
              </div>
            )}
          </div>
        ) : (
          <>
            {messages.map((message) => (
              <div
                key={`${message.id}-${message.created_at}`} // Better key to prevent duplicates
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
                  <div className="flex items-center justify-between mt-1">
                    <p className="text-xs opacity-60">
                      {formatTime(message.created_at)}
                    </p>
                    {message.isUser && (
                      <div className="flex items-center gap-1">
                        <div className="w-1 h-1 bg-current opacity-60 rounded-full"></div>
                        <div className="w-1 h-1 bg-current opacity-60 rounded-full"></div>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}

            {/* Typing Indicator */}
            {otherUserTyping && <TypingIndicator />}
          </>
        )}
        {/* Invisible element to scroll to */}
        <div ref={messagesEndRef} />
      </div>

      {/* Input Area - Fixed at bottom */}
      <div className="p-6 border-t border-zinc-800">
        <div className="relative">
          <Input
            value={inputValue}
            onChange={handleInputChange}
            onKeyPress={handleKeyPress}
            placeholder={isConnected ? "Type your message..." : "Connecting..."}
            disabled={sending || !isConnected}
            className="w-full bg-zinc-800 border-zinc-700 rounded-2xl px-4 py-3 pr-12 text-white placeholder:text-zinc-500 focus:ring-2 focus:ring-zinc-600 focus:border-transparent disabled:opacity-50"
          />
          <Button
            onClick={handleSendMessage}
            size="icon"
            disabled={!inputValue.trim() || sending || !isConnected}
            className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-zinc-700 hover:bg-zinc-600 disabled:opacity-50 w-8 h-8"
          >
            {sending ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <ArrowUp className="w-4 h-4" />
            )}
          </Button>
        </div>
        {!isConnected && (
          <p className="text-xs text-zinc-500 mt-2 text-center">
            Waiting for real-time connection...
          </p>
        )}
      </div>
    </div>
  )
}
