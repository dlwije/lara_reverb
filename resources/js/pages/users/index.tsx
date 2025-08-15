// DemoPage.tsx
'use client';

import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Skeleton } from '@/components/ui/skeleton';
import { useConversations } from '@/hooks/use-conversations';
import { useEchoChat } from '@/hooks/use-echo-chat';
import { serverColumns } from '@/pages/users/server-columns';
import { ServerDataTable } from '@/pages/users/server-data-table';
import { Conversation } from '@/types/chat';
import { setupEcho } from '@/utils/echo-setup';
import { Button } from '@headlessui/react';
import { VisuallyHidden } from '@radix-ui/react-visually-hidden';
import { AlertCircle, ArrowLeft, MessageCircle, Plus, Radio, Send, WifiOff } from 'lucide-react';
import { ConversationList } from '@/components/conversation-list';
import { ChatInterface } from '@/components/chat-interface';
import { ComposeMessageDialog } from '@/components/compose-message-dialog';
import { DebugServerDataTable } from '@/pages/users/debug-server-data-table';
import { ApiConnectionTest } from '@/components/api-connection-test';

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

    const [chatOpen, setChatOpen] = useState(false)
    const [selectedConversationId, setSelectedConversationId] = useState<number | null>(null)

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
        console.log('handleSelectConversation', conversation);
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

    const handleMessageSent = (conversationId: number, targetUser: User) => {
        console.log(`✅ Message sent to ${targetUser.name}, conversation ID: ${conversationId}`)
        // Optionally open the chat window to show the new conversation
        setSelectedConversationId(conversationId)
        setChatOpen(true)
    }

    // const { messages, loading, sending, error, sendMessage, refreshMessages } = useChat(selectedUser?.id, user?.id)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-2 md:py-2">
                        <div className="flex items-center justify-between">
                        <ComposeMessageDialog
                            currentUser={user}
                            authToken={authToken}
                            onMessageSent={handleMessageSent}
                            trigger={
                                <Button>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Compose Message
                                </Button>
                            }
                        />
                        </div>
                        <div>
                            {/* API Connection Test */}
                            {/*<ApiConnectionTest authToken={authToken} userId={user?.id} />*/}
                            {/*{chatLoading ? (*/}
                            {/*    <p className="text-center">Loading...</p>*/}
                            {/*) : (*/}
                            {/*     <DataTable columns={getColumns(setConfirmingUser, composeMessage)} data={users} />*/}
                                <ServerDataTable
                                    columns={serverColumns(setConfirmingUser, composeMessage)}
                                    apiEndpoint="/api/v1/users/list/data" // Replace with your actual API endpoint
                                    title="Users Management"
                                    searchPlaceholder="Search by email..."
                                    searchColumn="email"
                                />
                            {/*)}*/}

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
                                </SheetTrigger>
                                <SheetContent className="w-full max-w-4xl bg-zinc-900 border-zinc-800 text-white p-0 flex">
                                    {/* Conversation List */}
                                    {(showConversationList || !selectedConversation) && (
                                        <ConversationList
                                            currentUserId={user?.id}
                                            onSelectConversation={handleSelectConversation}
                                            selectedConversationId={selectedConversation?.conversation_id}
                                        />
                                    )}
                                    {selectedConversation && !showConversationList && (
                                        <div className="flex-1 flex flex-col">
                                            <SheetHeader className="p-4 border-b border-zinc-800">
                                                <SheetTitle>
                                                    <div className="flex items-center gap-3">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={handleBackToList}
                                                            className="text-zinc-400 hover:text-white lg:hidden"
                                                        >
                                                            <ArrowLeft className="w-4 h-4" />
                                                        </Button>
                                                        <div className="flex-1">
                                                            <h2 className="font-semibold text-lg">Chat with {selectedConversation.user.name}</h2>
                                                            <p className="text-zinc-400 text-sm">{selectedConversation.user.email}</p>
                                                        </div>
                                                    </div>
                                                </SheetTitle>
                                            </SheetHeader>

                                            <VisuallyHidden>
                                                <SheetDescription>Chat interface for selected conversation</SheetDescription>
                                            </VisuallyHidden>

                                            <ChatInterface
                                                user={{
                                                    id: selectedConversation.user.id.toString(),
                                                    name: selectedConversation.user.name,
                                                    email: selectedConversation.user.email,
                                                    avatar: selectedConversation.user.avatar || undefined,
                                                }}
                                                messages={[]} // This will be populated by your chat hook
                                                onSendMessage={(message) => {
                                                    console.log("Sending message:", message)
                                                    // Add your sendMessage logic here
                                                }}
                                                sending={false}
                                                isConnected={true}
                                                otherUserTyping={false}
                                                onTyping={() => {}}
                                                currentUserId={user?.id}
                                            />
                                        </div>
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
