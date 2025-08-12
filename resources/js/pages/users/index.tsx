// DemoPage.tsx
'use client';

import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { ChatInterface } from '@/components/chat-interface';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Skeleton } from '@/components/ui/skeleton';
import { useConversations } from '@/hooks/use-conversations';
import { useEchoChat } from '@/hooks/use-echo-chat';
import { serverColumns } from '@/pages/users/server-columns';
import { ServerDataTable } from '@/pages/users/server-data-table';
import { Conversation } from '@/types/chat';
import { setupEcho } from '@/utils/echo-setup';
import { Button } from '@headlessui/react';
import { VisuallyHidden } from '@radix-ui/react-visually-hidden';
import { AlertCircle, ArrowLeft, MessageCircle, Radio, Send, WifiOff } from 'lucide-react';
import { ConversationList } from '@/components/conversation-list';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users/list',
    },
];

interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    phone: string | null;
    user_type: string;
    updated_at: string;
    role: string;
    DT_RowIndex: number;
}

export default function DemoPage() {
    const { auth } = usePage<SharedData>().props;
    const echo = setupEcho();

    const [users, setUsers] = useState<User[]>([]);
    const [userLoading, setUserLoading] = useState(true);
    const [confirmingUser, setConfirmingUser] = useState<User | null>(null);

    const user = auth.user;
    const [selectedUser, setSelectedUser] = useState<User | null>(null);
    const [open, setOpen] = useState(false);

    const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null);
    const [showConversationList, setShowConversationList] = useState(true);

    // Get auth token from your auth system
    const authToken = auth.accessToken; // Replace with actual token

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
    } = useConversations(user?.id);

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
    } = useEchoChat(user?.id, selectedConversation?.user.id || 0, authToken, selectedConversation?.conversation_id);

    const handleSelectConversation = async (conversation: Conversation) => {
        setSelectedConversation(conversation)
        setShowConversationList(false)

        // Mark conversation as read
        if (conversation.unread_count > 0) {
            await markAsRead(conversation.conversation_id)
        }
    }

    const handleBackToList = () => {
        setSelectedConversation(null)
        setShowConversationList(true)
    }

    const getConnectionStatus = () => {
        if (isConnected) {
            return (
                <div className="flex items-center gap-2 text-green-400">
                    <Radio className="w-4 h-4" />
                    <span className="text-xs">Live</span>
                </div>
            )
        }
        return (
            <div className="flex items-center gap-2 text-yellow-400">
                <WifiOff className="w-4 h-4" />
                <span className="text-xs">Offline</span>
            </div>
        )
    }

    const getErrorIcon = () => {
        if (chatError?.includes("timeout") || chatError?.includes("connection")) {
            return <WifiOff className="w-4 h-4" />
        }
        return <AlertCircle className="h-4 w-4" />;
    };


    const totalUnreadCount = getTotalUnreadCount()

    const composeMessage = (member) => {
        setSelectedUser(member);
        console.log('SelectedUserId: ' + member.id);
        console.log('SelectedUserName: ' + member.name);
        setOpen(true);
    };

    const openChatWindow = async () => {
        setOpen(true);
    }

    // const { messages, loading, sending, error, sendMessage, refreshMessages } = useChat(selectedUser?.id, user?.id)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-2 md:py-2">
                        <button className="cursor-pointer inline-flex h-8 w-8 items-center justify-center rounded hover:bg-zinc-100 text-green-600" onClick={openChatWindow}>
                            <Send className="h-4 w-4" />
                        </button>
                        <div>
                            {chatLoading ? (
                                <p className="text-center">Loading...</p>
                            ) : (
                                // <DataTable columns={getColumns(setConfirmingUser, composeMessage)} data={users} />
                                <ServerDataTable
                                    columns={serverColumns(setConfirmingUser)}
                                    apiEndpoint="/api/v1/users/list/data" // Replace with your actual API endpoint
                                    title="Users Management"
                                    searchPlaceholder="Search by email..."
                                    searchColumn="email"
                                />
                            )}

                            {/* Modal */}
                            {confirmingUser && (
                                <div className="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black">
                                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                                        <h2 className="mb-4 text-lg font-semibold">
                                            Delete <span className="text-red-600">{confirmingUser.name}</span>?
                                        </h2>
                                        <p className="mb-6">This action cannot be undone.</p>
                                        <div className="flex justify-end gap-3">
                                            <button onClick={() => setConfirmingUser(null)} className="rounded-md border px-4 py-2 text-gray-700">
                                                Cancel
                                            </button>
                                            <button
                                                onClick={() => deleteUser(confirmingUser)}
                                                className="rounded-md bg-red-600 px-4 py-2 text-white hover:bg-red-700"
                                            >
                                                Confirm Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <Sheet open={open} onOpenChange={setOpen}>
                                <SheetTrigger asChild>
                                    {/*<Button className="w-full" size="lg">*/}
                                    {/*    <MessageCircle className="w-5 h-5 mr-2" />*/}
                                    {/*    Open Messages*/}
                                    {/*    {totalUnreadCount > 0 && (*/}
                                    {/*        <span className="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">{totalUnreadCount}</span>*/}
                                    {/*    )}*/}
                                    {/*</Button>*/}
                                </SheetTrigger>
                                <SheetContent className="w-full max-w-4xl bg-zinc-900 border-zinc-800 text-white p-0 flex">
                                    {/* Conversation List */}
                                    {/*{(showConversationList || !selectedConversation) && (*/}
                                    {/*    <ConversationList*/}
                                    {/*        currentUserId={user?.id}*/}
                                    {/*        onSelectConversation={handleSelectConversation}*/}
                                    {/*        selectedConversationId={selectedConversation?.conversation_id}*/}
                                    {/*    />*/}
                                    {/*)}*/}
                                    <SheetHeader className="p-6">
                                        <SheetTitle>
                                            <div className="flex items-center gap-3">
                                                {!showConversationList && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={handleBackToList}
                                                        className="text-zinc-400 hover:text-white lg:hidden"
                                                    >
                                                        <ArrowLeft className="w-4 h-4" />
                                                    </Button>
                                                )}

                                                <div className="flex-1">
                                                    {/*<h2 className="font-semibold text-lg">Chat with {selectedConversation?.user.name}</h2>*/}
                                                    {/*<p className="text-zinc-400 text-sm">*/}
                                                    {/*    {selectedConversation?.user.email}*/}
                                                    {/*    {conversationId && <span className="ml-2">• Conv: {conversationId}</span>}*/}
                                                    {/*    {otherUserTyping && <span className="text-green-400 ml-2">• typing...</span>}*/}
                                                    {/*</p>*/}
                                                </div>

                                            </div>
                                        </SheetTitle>
                                    </SheetHeader>
                                    <VisuallyHidden>
                                        <SheetDescription className="p-6"></SheetDescription>
                                    </VisuallyHidden>

                                    {chatError && (
                                        <div className="p-4 bg-red-900/20 border-b border-red-800">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2 text-red-400">
                                                    {getErrorIcon()}
                                                    <span className="text-sm">{chatError}</span>
                                                </div>
                                                {!isConnected && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={reconnect}
                                                        className="text-red-400 hover:text-red-300"
                                                    >
                                                        Reconnect
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    {chatLoading ? (
                                        <div className="flex items-center space-x-4 p-6">
                                            <Skeleton className="h-12 w-12 rounded-full" />
                                            <div className="space-y-2">
                                                <Skeleton className="h-4 w-[250px]" />
                                                <Skeleton className="h-4 w-[200px]" />
                                            </div>
                                        </div>
                                    ) : (
                                        <ConversationList
                                            user={{
                                                id: selectedConversation?.user.id.toString(),
                                                name: selectedConversation?.user.name,
                                                email: selectedConversation?.user.email,
                                                avatar: selectedConversation?.user.avatar || undefined,
                                            }}
                                            messages={messages}
                                            onSendMessage={sendMessage}
                                            sending={sending}
                                            isConnected={isConnected}
                                            otherUserTyping={otherUserTyping}
                                            onTyping={handleTyping}
                                        />
                                    )}
                                </SheetContent>
                            </Sheet>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
