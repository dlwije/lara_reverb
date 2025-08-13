'use client';

import type React from 'react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Textarea } from '@/components/ui/textarea';
import { getOrCreateConversation, sendMessageToBackend } from '@/utils/chat-api';
import { Loader2, Search, Send } from 'lucide-react';
import { useState } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
}

interface ComposeMessageDialogProps {
    currentUser: User;
    authToken: string;
    trigger?: React.ReactNode;
    selectedUser?: User | null; // Pre-selected user
    onMessageSent?: (conversationId: number, user: User) => void;
}

export function ComposeMessageDialog({ currentUser, authToken, trigger, selectedUser, onMessageSent }: ComposeMessageDialogProps) {
    const [open, setOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedRecipient, setSelectedRecipient] = useState<User | null>(selectedUser || null);
    const [message, setMessage] = useState('');
    const [sending, setSending] = useState(false);
    const [users, setUsers] = useState<User[]>([]);
    const [searchLoading, setSearchLoading] = useState(false);

    // Search users function
    const searchUsers = async (query: string) => {
        if (!query.trim()) {
            setUsers([]);
            return;
        }

        setSearchLoading(true);
        try {
            // Replace with your actual API endpoint
            const response = await fetch(`/api/v1/conversation/users/search?q=${encodeURIComponent(query)}`, {
                headers: {
                    Authorization: `Bearer ${authToken}`,
                    'Content-Type': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                setUsers(data.data || []);
            }
        } catch (error) {
            console.error('Failed to search users:', error);
        } finally {
            setSearchLoading(false);
        }
    };

    const handleSearchChange = (value: string) => {
        setSearchQuery(value);
        searchUsers(value);
    };

    const handleSendMessage = async () => {
        if (!selectedRecipient || !message.trim() || sending) return;

        setSending(true);
        try {
            // First, get or create conversation
            const conversation = await getOrCreateConversation(currentUser.id, selectedRecipient.id);

            // Then send the message
            const payload = {
                conversation_id: conversation.id,
                user_id: selectedRecipient.id,
                from: currentUser.id,
                message: message.trim(),
            };

            const sentMessage = await sendMessageToBackend(payload);

            if (sentMessage) {
                // Reset form
                setMessage('');
                setSelectedRecipient(null);
                setSearchQuery('');
                setUsers([]);
                setOpen(false);

                // Notify parent component
                if (onMessageSent) {
                    onMessageSent(conversation.id, selectedRecipient);
                }

                console.log('✅ Message sent successfully!');
            }
        } catch (error) {
            console.error('❌ Failed to send message:', error);
        } finally {
            setSending(false);
        }
    };

    const defaultTrigger = (
        <Button>
            <Send className="mr-2 h-4 w-4" />
            Compose Message
        </Button>
    );

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger || defaultTrigger}</DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Compose Message</DialogTitle>
                    <DialogDescription>Send a message to a team member</DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    {/* Recipient Selection */}
                    {!selectedRecipient ? (
                        <div className="space-y-2">
                            <label className="text-sm font-medium">To:</label>
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                <Input
                                    placeholder="Search users by name or email..."
                                    value={searchQuery}
                                    onChange={(e) => handleSearchChange(e.target.value)}
                                    className="pl-10"
                                />
                            </div>

                            {/* Search Results */}
                            {searchQuery && (
                                <ScrollArea className="h-32 rounded-md border">
                                    {searchLoading ? (
                                        <div className="flex items-center justify-center p-4">
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                            <span className="ml-2 text-sm">Searching...</span>
                                        </div>
                                    ) : users.length > 0 ? (
                                        <div className="space-y-1 p-2">
                                            {users.map((user) => (
                                                <button
                                                    key={user.id}
                                                    onClick={() => setSelectedRecipient(user)}
                                                    className="flex w-full items-center gap-3 rounded-md p-2 text-left hover:bg-gray-100"
                                                >
                                                    <Avatar className="h-8 w-8">
                                                        <AvatarImage src={user.avatar || '/placeholder.svg'} alt={user.name} />
                                                        <AvatarFallback>
                                                            {user.name
                                                                .split(' ')
                                                                .map((n) => n[0])
                                                                .join('')
                                                                .toUpperCase()}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate font-medium">{user.name}</p>
                                                        <p className="truncate text-sm text-gray-500">{user.email}</p>
                                                    </div>
                                                </button>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="flex items-center justify-center p-4 text-sm text-gray-500">No users found</div>
                                    )}
                                </ScrollArea>
                            )}
                        </div>
                    ) : (
                        <div className="space-y-2">
                            <label className="text-sm font-medium">To:</label>
                            <div className="flex items-center gap-3 rounded-md border bg-gray-50 p-3">
                                <Avatar className="h-8 w-8">
                                    <AvatarImage src={selectedRecipient.avatar || '/placeholder.svg'} alt={selectedRecipient.name} />
                                    <AvatarFallback>
                                        {selectedRecipient.name
                                            .split(' ')
                                            .map((n) => n[0])
                                            .join('')
                                            .toUpperCase()}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-medium">{selectedRecipient.name}</p>
                                    <p className="truncate text-sm text-gray-500">{selectedRecipient.email}</p>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setSelectedRecipient(null)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    ×
                                </Button>
                            </div>
                        </div>
                    )}

                    {/* Message Input */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Message:</label>
                        <Textarea
                            placeholder="Type your message here..."
                            value={message}
                            onChange={(e) => setMessage(e.target.value)}
                            rows={4}
                            className="resize-none"
                        />
                    </div>

                    {/* Actions */}
                    <div className="flex justify-end gap-2">
                        <Button variant="outline" onClick={() => setOpen(false)} disabled={sending}>
                            Cancel
                        </Button>
                        <Button onClick={handleSendMessage} disabled={!selectedRecipient || !message.trim() || sending}>
                            {sending ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Sending...
                                </>
                            ) : (
                                <>
                                    <Send className="mr-2 h-4 w-4" />
                                    Send Message
                                </>
                            )}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
