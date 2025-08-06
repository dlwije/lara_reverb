// DemoPage.tsx
'use client'

import { useEffect, useState } from "react"
import { DataTable } from "./data-table"
import { getColumns } from './columns';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { axios } from '@/lib/axios';
import toast from 'react-hot-toast';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users/list',
    },
];

interface User {
    id: number
    name: string
    email: string
    phone: string | null
    user_type: string
    updated_at: string
    role: string
    DT_RowIndex: number
}

export default function DemoPage() {
    const [users, setUsers] = useState<User[]>([])
    const [loading, setLoading] = useState(true)
    const [confirmingUser, setConfirmingUser] = useState<User | null>(null)

    const fetchUsers = async () => {
        try {
            const res = await axios.get('/api/v1/users/list/data')
            setUsers(res.data.data)
        } catch (err) {
            toast.error('Failed to load users')
        } finally {
            setLoading(false)
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
        fetchUsers()
    }, [])

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                        <div className="m-4">
                            {loading ? (
                                <p className="text-center">Loading...</p>
                            ) : (
                                <DataTable columns={getColumns(setConfirmingUser)} data={users} />
                            )}

                            {/* Modal */}
                            {confirmingUser && (
                                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
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
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>

    )
}
