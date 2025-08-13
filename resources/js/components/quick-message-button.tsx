"use client"

import { ComposeMessageDialog } from "./compose-message-dialog"
import { Button } from "@/components/ui/button"
import { Send } from "lucide-react"

interface User {
    id: number
    name: string
    email: string
    avatar?: string | null
}

interface QuickMessageButtonProps {
    user: User
    currentUser: User
    authToken: string
    onMessageSent?: (conversationId: number, user: User) => void
    variant?: "default" | "outline" | "ghost"
    size?: "sm" | "default" | "lg"
}

export function QuickMessageButton({
                                       user,
                                       currentUser,
                                       authToken,
                                       onMessageSent,
                                       variant = "outline",
                                       size = "sm",
                                   }: QuickMessageButtonProps) {
    const trigger = (
        <Button variant={variant} size={size}>
            <Send className="w-4 h-4 mr-2" />
            Message
        </Button>
    )

    return (
        <ComposeMessageDialog
            currentUser={currentUser}
            authToken={authToken}
            selectedUser={user}
            trigger={trigger}
            onMessageSent={onMessageSent}
        />
    )
}
