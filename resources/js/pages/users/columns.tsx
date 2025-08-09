import { ColumnDef } from "@tanstack/react-table"
import { User } from '@/types';

import { Checkbox } from "@/components/ui/checkbox"
import { Button } from '@headlessui/react';
import { ArrowUpDown, Pencil, Send, Trash2 } from 'lucide-react';

// This type is used to define the shape of our data.
// You can use a Zod schema here if you want.

export const getColumns = (
    onDelete: (user: User) => void,
    onMessage: (user: User) => void,
): ColumnDef<User>[] => [
    {
        id: "select",
        header: ({ table }) => (
            <Checkbox
                checked={
                    table.getIsAllPageRowsSelected() ||
                    (table.getIsSomePageRowsSelected() && "indeterminate")
                }
                onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
                aria-label="Select all"
            />
        ),
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Select row"
            />
        ),
        enableSorting: false,
        enableHiding: false,
    },
    { accessorKey: 'DT_RowIndex', header: '#' },
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'email', header: 'Email' },
    { accessorKey: 'role', header: 'Role' },
    {
        accessorKey: "updated_at",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() =>
                    column.toggleSorting(column.getIsSorted() === "asc")
                }
                className="h-auto p-0 inline-flex items-center gap-1 whitespace-nowrap"
            >
                <span>Updated</span>
                <ArrowUpDown className="h-4 w-4 shrink-0" />
            </Button>
        ),
        // If you intend to sort by email instead, set accessorKey: "email"
        cell: ({ row }) => <div className="lowercase">{row.getValue("updated_at")}</div>,
    },
    {
        id: 'actions',
        header: 'Actions',
        enableHiding: false,
        cell: ({ row }) => {
            const user = row.original

            return (
                <div className="flex items-center gap-1.5">
                    <a
                        href={`/auth/users/${user.id}/edit`}
                        aria-label="Edit"
                        className="inline-flex h-8 w-8 items-center justify-center rounded hover:bg-zinc-100"
                        title="Edit"
                    >
                        <Pencil className="h-4 w-4" />
                    </a>
                    <button
                        onClick={() => onDelete(user)}
                        aria-label="Delete"
                        className="cursor-pointer inline-flex h-8 w-8 items-center justify-center rounded hover:bg-zinc-100 text-red-600"
                        title="Delete"
                    >
                        <Trash2 className="h-4 w-4" />
                    </button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Send message"
                        onClick={() => onMessage(user)}
                        className="cursor-pointer inline-flex h-8 w-8 items-center justify-center rounded hover:bg-zinc-100 text-green-600"
                        title="Send message"
                    >
                        <Send className="h-4 w-4" />
                    </Button>
                </div>
            )
        },
    },
]
