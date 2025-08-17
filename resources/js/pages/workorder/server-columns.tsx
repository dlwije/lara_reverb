"use client"
import { User } from '@/types';
import type { ColumnDef } from "@tanstack/react-table"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { ArrowUpDown, Pencil, Send, Trash2 } from "lucide-react"
import { Checkbox } from "@/components/ui/checkbox"
// This type is used to define the shape of our data.
// You can use a Zod schema here if you want.

export const serverColumns = (
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
                className="cursor-pointer"
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
    { accessorKey: 'serial_no',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}>
                    Serial No
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            )
        },
        cell: ({ row }) => <div className="font-medium">{row.getValue("serial_no")}</div>, },
    { accessorKey: 'wo_date',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}>
                    WO Date
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            )
        },
        cell: ({ row }) => <div className="lowercase">{row.getValue("wo_date")}</div>,
    },
    { accessorKey: 'name', header: 'Name',
        cell: ({ row }) => {
            const role = row.getValue("name") as string
            return <Badge variant={role === "sys_admin" ? "default" : "secondary"}>{role.replace("_", " ")}</Badge>
        },
    },
    {
        accessorKey: "engine_no",
        header: "Engine No",
        cell: ({ row }) => {
            const type = row.getValue("engine_no") as string
            return <Badge variant="outline">{type}</Badge>
        },
    },
    {
        accessorKey: "woStatus",
        header: "Workorder Status",
        cell: ({ row }) => {
            const type = row.getValue("woStatus") as string
            return <Badge variant="outline">{type}</Badge>
        },
    },
    {
        accessorKey: "updated_at",
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}>
                    Updated
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            )
        },
        // If you intend to sort by email instead, set accessorKey: "email"
        cell: ({ row }) => {
            const date = new Date(row.getValue("updated_at"))
            return <div>{date.toLocaleDateString()}</div>
        },
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
                    {/*<Button*/}
                    {/*    variant="ghost"*/}
                    {/*    size="icon"*/}
                    {/*    aria-label="Send message"*/}
                    {/*    onClick={() => onMessage(user)}*/}
                    {/*    className="cursor-pointer inline-flex h-8 w-8 items-center justify-center rounded hover:bg-zinc-100 text-green-600"*/}
                    {/*    title="Send message"*/}
                    {/*>*/}
                    {/*    <Send className="h-4 w-4" />*/}
                    {/*</Button>*/}
                </div>
            )
        },
    },
]
