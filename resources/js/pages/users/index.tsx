// DemoPage.tsx
'use client'

import { useEffect, useState } from "react"
import { DataTable } from "./data-table"
import { getColumns } from './columns';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { axios } from '@/lib/axios';
import toast from 'react-hot-toast';
import { useDisclosure } from '@/hooks/use-disclosure';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';

import { Skeleton } from '@/components/ui/skeleton';
import { ChatInterface } from '@/components/chat-interface';
import { VisuallyHidden } from '@radix-ui/react-visually-hidden'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useChat } from '@/hooks/use-chat';
import { AlertCircle, WifiOff } from 'lucide-react';
import useEcho from '@/lib/echo';

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
    // email_verified_at: string | null
    // created_at: string
    // status: string
    // gender: string | null
    // address: string | null
    // dob: string | null
    // nationality: string | null
    // marital_status: string | null
    // occupation: string | null
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

    const fetchUsers = async () => {
        try {
            const res = await axios.get('/api/v1/users/list/data')
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
            await axios.delete(`/api/v1/users/${user.id}`)
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
        // setMessageTo(member.name)
        // setReceiver(member.id)
        // onOpen()
        //
        // setChatLoading(true);
        setOpen(true);
        if (echo) {
            const channel = echo.private(`chat.${user?.id}-${member.id}`);
            channel
                .subscribed(() => {
                    // This confirms the WebSocket connection AND authorization were successful.
                    console.log(
                        '✅ Successfully subscribed to the "online" presence channel!'
                    );
                }).listen('.PrivateMessageEvent', (e) => {
                    console.log(e);
            })
            console.log('Attempting to join chat channel:', channel);
        }
    }

    const { messages, loading, sending, error, sendMessage, refreshMessages } = useChat(selectedUser?.id, user?.id)

    const getErrorIcon = () => {
        if (error?.includes('timeout') || error?.includes('connection')) {
            return <WifiOff className="w-4 h-4" />
        }
        return <AlertCircle className="w-4 h-4" />
    }

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
                                    {/* optional custom trigger */}
                                </SheetTrigger>
                                <SheetContent className="w-full rounded-lg max-w-md bg-zinc-900 border-zinc-800 text-white p-0 flex flex-col min-h-full">
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
                                                    <p className="text-zinc-400 text-sm">{selectedUser?.email}</p>
                                                </div>
                                                <button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={refreshMessages}
                                                    disabled={loading}
                                                    className="text-zinc-400 hover:text-white"
                                                >
                                                    Refresh
                                                </button>
                                            </div>
                                        </SheetTitle>
                                    </SheetHeader>

                                    {error && (
                                        <div className="p-4 bg-red-900/20 border-b border-red-800">
                                            <div className="flex items-center gap-2 text-red-400">
                                                {getErrorIcon()}
                                                <span className="text-sm">{error}</span>
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
