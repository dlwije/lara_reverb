import { ColumnDef } from "@tanstack/react-table"
import { User } from '@/types';

// import {
//     DropdownMenu,
//     DropdownMenuContent,
//     DropdownMenuItem,
//     DropdownMenuLabel,
//     DropdownMenuSeparator,
//     DropdownMenuTrigger
// } from '@/components/ui/dropdown-menu';
// import { Button } from '@headlessui/react';
// import { MoreHorizontal } from 'lucide-react';

import { Link } from '@inertiajs/react';
import { Button } from '@headlessui/react';

// This type is used to define the shape of our data.
// You can use a Zod schema here if you want.

export const getColumns = (
    onDelete: (user: User) => void,
    onMessage: (user: User) => void,
): ColumnDef<User>[] => [
    { accessorKey: 'DT_RowIndex', header: '#' },
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'email', header: 'Email' },
    { accessorKey: 'role', header: 'Role' },
    { accessorKey: 'updated_at', header: 'Updated' },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) => {
            const user = row.original

            return (
                <div className="flex items-center gap-2">
                    <a href={`/auth/users/${user.id}/edit`} className="text-blue-600 hover:underline text-sm">
                        Edit
                    </a>
                    <button
                        onClick={() => onDelete(user)}
                        className="text-red-600 hover:underline text-sm"
                    >
                        Delete
                    </button>
                    <Button
                        className="w-full bg-black py-6 text-white"
                        size="md"
                        radius="md"
                        onClick={() =>
                            onMessage(user)
                        }>
                        Send message
                    </Button>
                </div>
            )
        },
    },
]
