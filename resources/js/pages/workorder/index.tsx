// DemoPage.tsx
'use client';

import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { TableActions } from '@/components/table-actions';
import { serverColumns } from '@/pages/workorder/server-columns';
import { ServerDataTable } from '@/pages/workorder/server-data-table';

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

    const user = auth.user;
    const authToken = auth.accessToken;

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
                            currentUser={auth.user}
                            authToken={authToken}
                            // onMessageSent={}
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
                                apiEndpoint="/api/v1/workorder/list/data" // Replace with your actual API endpoint
                                title="Workorder Management"
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
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
