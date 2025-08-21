"use client"

import type { ColumnDef } from "@tanstack/react-table"
import { Button } from "@/components/ui/button"
import { MoreHorizontal, Eye, Edit, Trash2, Camera, ArrowUpDown, ArrowUp, ArrowDown } from "lucide-react"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"

interface Product {
    id: number
    name: string
    code: string
    type: string
    photo?: string | null
    brand?: { name: string }
    category?: { name: string }
    supplier?: { company?: string; name?: string }
    unit?: { name: string }
    cost: number
    price: number
    rack_location?: string
    taxes?: Array<{ name: string }>
    stocks?: Array<{ store_id: number; balance: number }>
    stores?: Array<{ id: number; pivot: { price: number; taxes: { taxes: Array<{ name: string }> } } }>
    dont_track_stock: boolean
    deleted_at?: string
}

export const productColumns = (
    viewRow: (row: Product) => void,
    editRow: (row: Product) => void,
    deleteRow: (row: Product) => void,
    setPhoto: (photo: string) => void,
    selectedStore?: number,
    deleting?: boolean,
    deleted?: boolean,
): ColumnDef<Product>[] => [
    {
        id: "photo",
        header: () => (
            <div className="flex items-center justify-center">
                <Camera className="h-5 w-5" />
                <span className="sr-only">Photo</span>
            </div>
        ),
        cell: ({ row }) => {
            const product = row.original
            return (
                <div className="flex items-center justify-center w-16">
                    {product.photo && (
                        <button
                            type="button"
                            onClick={() => setPhoto(product.photo!)}
                            className="-my-4 w-8 h-8 flex items-center justify-center"
                        >
                            <img alt="" src={product.photo || "/placeholder.svg"} className="rounded-sm max-w-full max-h-full" />
                        </button>
                    )}
                </div>
            )
        },
        enableSorting: false,
    },
    {
        accessorKey: "name",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                className="h-auto p-0 font-semibold"
            >
                Name
                {column.getIsSorted() === "asc" ? (
                    <ArrowUp className="ml-2 h-3 w-3" />
                ) : column.getIsSorted() === "desc" ? (
                    <ArrowDown className="ml-2 h-3 w-3" />
                ) : (
                    <ArrowUpDown className="ml-2 h-3 w-3" />
                )}
            </Button>
        ),
        cell: ({ row }) => (
            <div className="cursor-pointer font-semibold" onClick={() => viewRow(row.original)}>
                {row.getValue("name")}
            </div>
        ),
    },
    {
        accessorKey: "code",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                className="h-auto p-0 font-semibold"
            >
                Code
                {column.getIsSorted() === "asc" ? (
                    <ArrowUp className="ml-2 h-3 w-3" />
                ) : column.getIsSorted() === "desc" ? (
                    <ArrowDown className="ml-2 h-3 w-3" />
                ) : (
                    <ArrowUpDown className="ml-2 h-3 w-3" />
                )}
            </Button>
        ),
        cell: ({ row }) => (
            <div className="cursor-pointer" onClick={() => viewRow(row.original)}>
                {row.getValue("code")}
            </div>
        ),
    },
    {
        accessorKey: "type",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                className="h-auto p-0 font-semibold"
            >
                Type
                {column.getIsSorted() === "asc" ? (
                    <ArrowUp className="ml-2 h-3 w-3" />
                ) : column.getIsSorted() === "desc" ? (
                    <ArrowDown className="ml-2 h-3 w-3" />
                ) : (
                    <ArrowUpDown className="ml-2 h-3 w-3" />
                )}
            </Button>
        ),
        cell: ({ row }) => (
            <div className="cursor-pointer" onClick={() => viewRow(row.original)}>
                {row.getValue("type")}
            </div>
        ),
    },
    {
        accessorKey: "brand.name",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                className="h-auto p-0 font-semibold"
            >
                Brand
                {column.getIsSorted() === "asc" ? (
                    <ArrowUp className="ml-2 h-3 w-3" />
                ) : column.getIsSorted() === "desc" ? (
                    <ArrowDown className="ml-2 h-3 w-3" />
                ) : (
                    <ArrowUpDown className="ml-2 h-3 w-3" />
                )}
            </Button>
        ),
        cell: ({ row }) => (
            <div className="cursor-pointer" onClick={() => viewRow(row.original)}>
                {row.original.brand?.name || ""}
            </div>
        ),
    },
    {
        accessorKey: "category.name",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                className="h-auto p-0 font-semibold"
            >
                Category
                {column.getIsSorted() === "asc" ? (
                    <ArrowUp className="ml-2 h-3 w-3" />
                ) : column.getIsSorted() === "desc" ? (
                    <ArrowDown className="ml-2 h-3 w-3" />
                ) : (
                    <ArrowUpDown className="ml-2 h-3 w-3" />
                )}
            </Button>
        ),
        cell: ({ row }) => (
            <div className="cursor-pointer" onClick={() => viewRow(row.original)}>
                {row.original.category?.name || ""}
            </div>
        ),
    },
    {
        accessorKey: "supplier",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                className="h-auto p-0 font-semibold"
            >
                Supplier
                {column.getIsSorted() === "asc" ? (
                    <ArrowUp className="ml-2 h-3 w-3" />
                ) : column.getIsSorted() === "desc" ? (
                    <ArrowDown className="ml-2 h-3 w-3" />
                ) : (
                    <ArrowUpDown className="ml-2 h-3 w-3" />
                )}
            </Button>
        ),
        cell: ({ row }) => (
            <div className="cursor-pointer" onClick={() => viewRow(row.original)}>
                {row.original.supplier?.company || row.original.supplier?.name || ""}
            </div>
        ),
    },
    {
        accessorKey: "quantity",
        header: "Quantity",
        cell: ({ row }) => {
            const product = row.original
            const quantity =
                product.dont_track_stock || product.type !== "Standard"
                    ? ""
                    : selectedStore
                        ? product.stocks?.find((s) => s.store_id === selectedStore)?.balance || 0
                        : product.stocks?.reduce((a, s) => Number(s.balance) + a, 0) || 0

            return (
                <div className="cursor-pointer text-right" onClick={() => viewRow(row.original)}>
                    {quantity ? new Intl.NumberFormat().format(Number(quantity)) : ""}
                </div>
            )
        },
        enableSorting: false,
    },
    {
        accessorKey: "unit.name",
        header: "Unit",
        cell: ({ row }) => (
            <div className="cursor-pointer" onClick={() => viewRow(row.original)}>
                {row.original.unit?.name || ""}
            </div>
        ),
        enableSorting: false,
    },
    {
        accessorKey: "cost",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                className="h-auto p-0 font-semibold"
            >
                Cost
                {column.getIsSorted() === "asc" ? (
                    <ArrowUp className="ml-2 h-3 w-3" />
                ) : column.getIsSorted() === "desc" ? (
                    <ArrowDown className="ml-2 h-3 w-3" />
                ) : (
                    <ArrowUpDown className="ml-2 h-3 w-3" />
                )}
            </Button>
        ),
        cell: ({ row }) => (
            <div className="cursor-pointer text-right" onClick={() => viewRow(row.original)}>
                ${Number(row.getValue("cost")).toFixed(2)}
            </div>
        ),
    },
    {
        accessorKey: "price",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                className="h-auto p-0 font-semibold"
            >
                Price
                {column.getIsSorted() === "asc" ? (
                    <ArrowUp className="ml-2 h-3 w-3" />
                ) : column.getIsSorted() === "desc" ? (
                    <ArrowDown className="ml-2 h-3 w-3" />
                ) : (
                    <ArrowUpDown className="ml-2 h-3 w-3" />
                )}
            </Button>
        ),
        cell: ({ row }) => {
            const product = row.original
            const price = selectedStore
                ? product.stores?.find((s) => s.id === selectedStore)?.pivot?.price || product.price
                : product.price

            return (
                <div className="cursor-pointer text-right" onClick={() => viewRow(row.original)}>
                    ${Number(price).toFixed(2)}
                </div>
            )
        },
    },
    {
        accessorKey: "taxes",
        header: "Taxes",
        cell: ({ row }) => {
            const product = row.original
            const taxes = selectedStore
                ? product.stores
                ?.find((s) => s.id === selectedStore)
                ?.pivot?.taxes?.taxes?.map((t) => t.name)
                ?.join(", ") || product.taxes?.map((t) => t.name)?.join(", ")
                : product.taxes?.map((t) => t.name)?.join(", ") || ""

            return (
                <div className="cursor-pointer" onClick={() => viewRow(row.original)}>
                    {taxes}
                </div>
            )
        },
        enableSorting: false,
    },
    {
        accessorKey: "rack_location",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                className="h-auto p-0 font-semibold"
            >
                Rack Location
                {column.getIsSorted() === "asc" ? (
                    <ArrowUp className="ml-2 h-3 w-3" />
                ) : column.getIsSorted() === "desc" ? (
                    <ArrowDown className="ml-2 h-3 w-3" />
                ) : (
                    <ArrowUpDown className="ml-2 h-3 w-3" />
                )}
            </Button>
        ),
        cell: ({ row }) => (
            <div className="cursor-pointer" onClick={() => viewRow(row.original)}>
                {row.getValue("rack_location") || ""}
            </div>
        ),
    },
    {
        id: "actions",
        header: () => <span className="sr-only">Actions</span>,
        cell: ({ row }) => {
            const product = row.original

            return (
                <div className="flex items-center justify-end gap-2">
                    {!product.dont_track_stock && product.type === "Standard" && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-8 w-8 p-0"
                            onClick={() => console.log("Track product:", product.id)}
                        >
                            <ArrowUpDown className="h-4 w-4" />
                            <span className="sr-only">Track</span>
                        </Button>
                    )}

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0">
                                <span className="sr-only">Open menu</span>
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => viewRow(product)}>
                                <Eye className="mr-2 h-4 w-4" />
                                View
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => editRow(product)}>
                                <Edit className="mr-2 h-4 w-4" />
                                Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => deleteRow(product)} className="text-red-600" disabled={deleting}>
                                <Trash2 className="mr-2 h-4 w-4" />
                                {deleting ? "Deleting..." : "Delete"}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            )
        },
        enableSorting: false,
    },
]
