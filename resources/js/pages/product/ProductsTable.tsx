"use client"

import { Head, usePage } from "@inertiajs/react"
import { useState } from "react"
import type { BreadcrumbItem, SharedData } from "@/types"
import { TableActions } from "@/components/table-actions"
import { productColumns } from "./product-columns"
import { Modal } from "@/components/ui/modal"
import { QuickView } from "./QuickView"
import AppLayout from '@/layouts/app-layout';
import { ServerDataTable } from '@/pages/users/server-data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Products",
        href: "/products",
    },
]

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
    DT_RowIndex: number
}

interface ProductsTableProps {
    pagination: {
        data: Product[]
        meta: any
        links: any
    }
    taxes: any[]
    selected_store?: number
    stores: Array<{ value: number; label: string }>
}

export default function ProductsTable({ pagination, taxes, selected_store, stores }: ProductsTableProps) {
    const { auth } = usePage<SharedData>().props
    const [view, setView] = useState(false)
    const [photo, setPhoto] = useState<string | null>(null)
    const [current, setCurrent] = useState<Product | null>(null)
    const [deleted, setDeleted] = useState(false)
    const [deleting, setDeleting] = useState(false)
    const [selectedProducts, setSelectedProducts] = useState<Product[]>([])

    const viewRow = (row: Product) => {
        setCurrent(row)
        setView(true)
    }

    const editRow = (row: Product) => {
        // router.visit(route('products.edit', { product: row.id }));
        console.log("Edit product:", row.id)
    }

    const deleteRow = (row: Product) => {
        setDeleting(true)
        // router.delete(route('products.destroy', row.id), {
        //   preserveScroll: true,
        //   onSuccess: () => setDeleted(true),
        //   onFinish: () => setDeleting(false),
        // });
        console.log("Delete product:", row.id)
        setTimeout(() => {
            setDeleted(true)
            setDeleting(false)
        }, 1000)
    }

    const handleExport = () => {
        console.log("Exporting products...")
        // Add export logic here
    }

    const handleFilter = () => {
        console.log("Opening filters...")
        // Add filter logic here
    }

    const handleBulkAction = (action: string) => {
        console.log(`Bulk action: ${action}`)
        switch (action) {
            case "delete":
                console.log("Deleting selected products:", selectedProducts)
                break
            case "refresh":
                console.log("Refreshing data...")
                break
            case "import":
                console.log("Opening import dialog...")
                break
            case "settings":
                console.log("Opening table settings...")
                break
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Products" />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-2 md:py-2">
                        <TableActions
                            currentUser={auth.user}
                            authToken={auth.accessToken}
                            onExport={handleExport}
                            onFilter={handleFilter}
                            onBulkAction={handleBulkAction}
                            selectedCount={selectedProducts.length}
                            title="Products"
                            createRoute="/products/create"
                            createPermission="create-products"
                            importRoute="/products/import"
                            exportRoute="/products/export"
                            importPermission="import-products"
                            exportPermission="export-products"
                        />

                        <div>
                            <ServerDataTable
                                columns={productColumns(viewRow, editRow, deleteRow, setPhoto, selected_store, deleting, deleted)}
                                apiEndpoint="/api/v1/product/list/data"
                                title="Product Management"
                                searchPlaceholder="Search products..."
                                searchColumn="name"
                                initialData={pagination}
                                filters={{
                                    store: selected_store,
                                    stores: stores,
                                }}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Quick View Modal */}
            <Modal show={view} onClose={() => setView(false)} maxWidth="3xl">
                <QuickView current={current} onClose={() => setView(false)} editRow={editRow} />
            </Modal>

            {/* Photo Modal */}
            <Modal show={!!photo} onClose={() => setPhoto(null)} maxWidth="2xl" transparent>
                <div className="flex items-center justify-center">
                    <img alt="" src={photo || ""} className="rounded-md w-full h-full max-w-full min-h-24 max-h-screen" />
                </div>
            </Modal>
        </AppLayout>
    )
}
