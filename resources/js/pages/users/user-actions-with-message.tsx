'use client';

import { ComposeMessageDialog } from '@/components/compose-message-dialog';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Edit, MessageCircle, MoreHorizontal, Trash2 } from 'lucide-react';

interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
}

interface UserActionsWithMessageProps {
    user: User;
    currentUser: User;
    authToken: string;
    onEdit?: (user: User) => void;
    onDelete?: (user: User) => void;
    onMessageSent?: (conversationId: number, user: User) => void;
}

export function UserActionsWithMessage({ user, currentUser, authToken, onEdit, onDelete, onMessageSent }: UserActionsWithMessageProps) {
    const messageTrigger = (
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>
            <MessageCircle className="mr-2 h-4 w-4" />
            Send Message
        </DropdownMenuItem>
    );

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm">
                    <MoreHorizontal className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <ComposeMessageDialog
                    currentUser={currentUser}
                    authToken={authToken}
                    selectedUser={user}
                    trigger={messageTrigger}
                    onMessageSent={onMessageSent}
                />
                {onEdit && (
                    <DropdownMenuItem onClick={() => onEdit(user)}>
                        <Edit className="mr-2 h-4 w-4" />
                        Edit
                    </DropdownMenuItem>
                )}
                {onDelete && (
                    <DropdownMenuItem onClick={() => onDelete(user)} className="text-red-600">
                        <Trash2 className="mr-2 h-4 w-4" />
                        Delete
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
