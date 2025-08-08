// DemoPage.tsx
'use client'

import { useEffect, useState } from "react"
import { DataTable } from "./data-table"
import { getColumns } from './columns';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import toast from 'react-hot-toast';
import { useDisclosure } from '@/hooks/use-disclosure';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';

import { Skeleton } from '@/components/ui/skeleton';
import { ChatInterface } from '@/components/chat-interface';
import { VisuallyHidden } from '@radix-ui/react-visually-hidden'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useChat } from '@/hooks/use-chat';
import { AlertCircle, MessageCircle, Radio, WifiOff } from 'lucide-react';
import useEcho from '@/lib/echo';
import apiClient from '@/lib/apiClient';
import { useEchoChat } from '@/hooks/use-echo-chat';
import { Button } from '@headlessui/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users/list',
    },
];

type Message = {
    id: number
    text: string
    isUser: boolean // Optional: derive from sender_id === selectedUser.id
    sender_id: number
    receiver_id: number
    created_at: string
}

interface User {
    id: number
    name: string
    email: string
    avatar?: string | null
    phone: string | null
    user_type: string
    updated_at: string
    role: string
    DT_RowIndex: number
}

export default function DemoPage() {

    const { auth } = usePage<SharedData>().props;
    const echo = useEcho()

    const [users, setUsers] = useState<User[]>([])
    const [userLoading, setUserLoading] = useState(true)
    const [confirmingUser, setConfirmingUser] = useState<User | null>(null)

    const user = auth.user;
    const [selectedUser, setSelectedUser] = useState<User | null>(null);

    const [open, setOpen] = useState(false);

    // Get auth token from your auth system
    const authToken = auth.accessToken // Replace with actual token

    const {
        messages,
        loading,
        sending,
        error,
        isConnected,
        otherUserTyping,
        sendMessage,
        refreshMessages,
        reconnect,
        handleTyping
    } = useEchoChat(user?.id, selectedUser?.id, authToken)

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
        if (error?.includes('timeout') || error?.includes('connection')) {
            return <WifiOff className="w-4 h-4" />
        }
        return <AlertCircle className="w-4 h-4" />
    }


    const fetchUsers = async () => {
        try {
            const res = await apiClient.get('/api/v1/users/list/data')
            setUsers(res.data.data)
            setUserLoading(false)
        } catch (err) {
            toast.error('Failed to load users')
        } finally {
            setUserLoading(false)
        }
    }

    const deleteUser = async (user: User) => {
        try {
            await apiClient.delete(`/api/v1/users/${user.id}`)
            toast.success('User deleted successfully')
            setUsers(prev => prev.filter(u => u.id !== user.id)) // ✅ update table without reload
            setConfirmingUser(null)
        } catch (err) {
            toast.error('Delete failed')
        }
    }

    useEffect(() => {
        setUserLoading(true);
        fetchUsers()
    }, [])

    const composeMessage = member => {
        setSelectedUser(member)
        console.log('SelectedUserId: '+member.id)
        console.log('SelectedUserName: '+member.name)
        // setMessageTo(member.name)
        // setReceiver(member.id)
        // onOpen()
        //
        // setChatLoading(true);
        setOpen(true);
        // if (echo) {
        //     const idA = Math.min(user.id, member.id)
        //     const idB = Math.max(user.id, member.id)
        //     const channelName = `chat.${idA}-${idB}`
        //     const channel = echo.private(channelName)
        //     console.log('Attempting to join chat channel on User List:', channelName);
        //     channel
        //         .subscribed(() => {
        //             // This confirms the WebSocket connection AND authorization were successful.
        //             console.log(
        //                 '✅ Successfully subscribed to the "chat" private channel on User List!'
        //             );
        //         }).listen('.MessageSent', (e) => {
        //             console.log(
        //                 'MessageSent on private channel User List!'
        //             );
        //             console.log(e);
        //     })
        //     console.log('Attempting to join chat channel on User List:', channel);
        // }
    }

    // const { messages, loading, sending, error, sendMessage, refreshMessages } = useChat(selectedUser?.id, user?.id)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                        <div>
                            {loading ? (
                                <p className="text-center">Loading...</p>
                            ) : (
                                <DataTable columns={getColumns(setConfirmingUser, composeMessage)} data={users} />
                            )}

                            {/* Modal */}
                            {confirmingUser && (
                                <div
                                    className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                                    <div className="bg-white rounded-lg p-6 shadow-lg w-full max-w-md">
                                        <h2 className="text-lg font-semibold mb-4">
                                            Delete <span className="text-red-600">{confirmingUser.name}</span>?
                                        </h2>
                                        <p className="mb-6">This action cannot be undone.</p>
                                        <div className="flex justify-end gap-3">
                                            <button
                                                onClick={() => setConfirmingUser(null)}
                                                className="px-4 py-2 rounded-md border text-gray-700"
                                            >
                                                Cancel
                                            </button>
                                            <button
                                                onClick={() => deleteUser(confirmingUser)}
                                                className="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700"
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
                                <SheetContent className="w-full max-w-md bg-zinc-900 border-zinc-800 text-white p-0 flex flex-col min-h-full">
                                    <SheetHeader className="p-6 border-b border-zinc-800">
                                        <SheetTitle>
                                            <div className="flex items-center gap-3">
                                                <Avatar className="w-12 h-12">
                                                    <AvatarImage src={selectedUser?.avatar || "/placeholder.svg"} alt={selectedUser?.name} />
                                                    <AvatarFallback className="bg-white text-black">
                                                        {selectedUser?.name?.split(' ').map(n => n[0]).join('')}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div className="flex-1">
                                                    <h2 className="font-semibold text-lg">Chat with {selectedUser?.name}</h2>
                                                    <p className="text-zinc-400 text-sm">
                                                      {selectedUser?.email}
                                                      {otherUserTyping && (
                                                        <span className="text-green-400 ml-2">• typing...</span>
                                                      )}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    {getConnectionStatus()}
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={refreshMessages}
                                                        disabled={loading}
                                                        className="text-zinc-400 hover:text-white"
                                                    >
                                                        Refresh
                                                    </Button>
                                                </div>
                                            </div>
                                        </SheetTitle>
                                    </SheetHeader>
                                    <VisuallyHidden>
                                        <SheetDescription className="p-6"></SheetDescription>
                                    </VisuallyHidden>
                                    {error && (
                                        <div className="p-4 bg-red-900/20 border-b border-red-800">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2 text-red-400">
                                                    {getErrorIcon()}
                                                    <span className="text-sm">{error}</span>
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

                                    {loading ? (
                                        <div className="flex items-center space-x-4 p-6">
                                            <Skeleton className="h-12 w-12 rounded-full" />
                                            <div className="space-y-2">
                                                <Skeleton className="h-4 w-[250px]" />
                                                <Skeleton className="h-4 w-[200px]" />
                                            </div>
                                        </div>
                                    ) : (
                                        <ChatInterface
                                            user={selectedUser}
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

    )
}
