// DemoPage.tsx
'use client';

import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { ChatInterface } from '@/components/chat-interface';
import { ConversationList } from '@/components/conversation-list';
import { TableActions } from '@/components/table-actions';
import { serverColumns } from '@/pages/users/server-columns';
import { ServerDataTable } from '@/pages/users/server-data-table';
import { ChatUser, Conversation } from '@/types/chat';
import { Button } from '@headlessui/react';
import { VisuallyHidden } from '@radix-ui/react-visually-hidden';
import { ArrowLeft } from 'lucide-react';

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
    const [confirmingUser, setConfirmingUser] = useState<User | null>(null);
    const [selectedUsers, setSelectedUsers] = useState<User[]>([]);

    // Chat specific states
    const [selectedUser, setSelectedUser] = useState<User | null>(null);
    const [open, setOpen] = useState(false);
    const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null);
    const [selectedConversationId, setSelectedConversationId] = useState<number | null>(null);
    const [showConversationList, setShowConversationList] = useState(true);

    const user = auth.user;
    const authToken = auth.accessToken;

    // Convert auth user to ChatUser format
    const currentChatUser: ChatUser = {
        id: user.id,
        name: user.name,
        email: user.email,
        avatar: user.avatar,
    };

    const handleSelectConversation = async (conversation: Conversation) => {
        console.log('handleSelectConversation', conversation);
        setSelectedConversation(conversation);
        setShowConversationList(false);

        // Mark conversation as read
        // if (conversation.unread_count > 0) {
        //     await markAsRead(conversation.conversation_id)
        // }
    };

    const handleBackToList = () => {
        setSelectedConversation(null);
        setShowConversationList(true);
    };

    const handleMessageSent = (conversationId: number, targetUser: ChatUser) => {
        console.log(`✅ Message sent to ${targetUser.name}, conversation ID: ${conversationId}`);
        // Handle message sent logic here
    };

    const handleExport = () => {
        console.log('Exporting users...');
        // Add export logic here
    };

    const handleFilter = () => {
        console.log('Opening filters...');
        // Add filter logic here
    };

    const handleBulkAction = (action: string) => {
        console.log(`Bulk action: ${action}`);
        switch (action) {
            case 'delete':
                console.log('Deleting selected users:', selectedUsers);
                break;
            case 'refresh':
                console.log('Refreshing data...');
                break;
            case 'import':
                console.log('Opening import dialog...');
                break;
            case 'settings':
                console.log('Opening table settings...');
                break;
        }
    };

    const composeMessage = (member) => {
        setSelectedUser(member);
        console.log('SelectedUserId: ' + member.id);
        console.log('SelectedUserName: ' + member.name);
        setOpen(true);
    };

    // const handleMessageSent = (conversationId: number, targetUser: User) => {
    //     console.log(`✅ Message sent to ${targetUser.name}, conversation ID: ${conversationId}`)
    //     // Optionally open the chat window to show the new conversation
    //     setSelectedConversationId(conversationId)
    //     setChatOpen(true)
    // }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-2 md:py-2">
                        <TableActions
                            currentUser={currentChatUser}
                            authToken={authToken}
                            onMessageSent={handleMessageSent}
                            onExport={handleExport}
                            onFilter={handleFilter}
                            onBulkAction={handleBulkAction}
                            selectedCount={selectedUsers.length}
                        />
                        <div>
                            {/* API Connection Test */}
                            {/*<ApiConnectionTest authToken={authToken} userId={user?.id} />*/}

                            <ServerDataTable
                                columns={serverColumns(setConfirmingUser, composeMessage)}
                                apiEndpoint="/api/v1/users/list/data" // Replace with your actual API endpoint
                                title="Users Management"
                                searchPlaceholder="Search by email..."
                                searchColumn="email"
                            />

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
                                <SheetTrigger asChild></SheetTrigger>
                                <SheetContent className="flex w-full max-w-4xl border-zinc-800 bg-zinc-900 p-0 text-white">
                                    {/* Conversation List */}
                                    {(showConversationList || !selectedConversation) && (
                                        <ConversationList
                                            currentUserId={user?.id}
                                            onSelectConversation={handleSelectConversation}
                                            selectedConversationId={selectedConversation?.conversation_id}
                                        />
                                    )}
                                    {selectedConversation && !showConversationList && (
                                        <div className="flex flex-1 flex-col">
                                            <SheetHeader className="border-b border-zinc-800 p-4">
                                                <SheetTitle>
                                                    <div className="flex items-center gap-3">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={handleBackToList}
                                                            className="text-zinc-400 hover:text-white lg:hidden"
                                                        >
                                                            <ArrowLeft className="h-4 w-4" />
                                                        </Button>
                                                        <div className="flex-1">
                                                            <h2 className="text-lg font-semibold">Chat with {selectedConversation.user.name}</h2>
                                                            <p className="text-sm text-zinc-400">{selectedConversation.user.email}</p>
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
                                                    console.log('Sending message:', message);
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
