import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { BreadcrumbItem, SharedData, User } from '@/types';
import { TableActions } from '@/components/table-actions';
import { ServerDataTable } from '@/pages/workorder/server-data-table';
import { serverColumns } from '@/pages/workorder/server-columns';
import AppLayout from '@/layouts/app-layout';

interface filters {
    store: string | null,
    trashed: string | null
}
interface ProductsIndexProps {
    pagination: any
    taxes: any[]
    selected_store: string | null
    stores: any[]
    filters: filters[]
}
export default function IndexProduct(props: ProductsIndexProps) {
    // const { props } = usePage<any>()
    const { auth } = usePage<SharedData>().props;
    const [selectedUsers, setSelectedUsers] = useState<User[]>([]);
    const [confirmingUser, setConfirmingUser] = useState<User | null>(null);

    const [view, setView] = useState(false)
    const [photo, setPhoto] = useState<string | null>(null)
    const [current, setCurrent] = useState<any>(null)
    const [deleted, setDeleted] = useState(false)
    const [deleting, setDeleting] = useState(false)
    const [searching, setSearching] = useState(false)

    const [filters, setFilters] = useState({
        store: props.selected_store || props.filters?.store || "",
        trashed: props.filters?.trashed || "",
        sort: props.filters?.sort || "",
    })

    useEffect(() => {
        if (props.selected_store) {
            setFilters((prev) => ({
                ...prev,
                store: props.filters?.store || props.selected_store,
            }))
        }
    }, [props.selected_store, props.filters])

    const viewRow = (row: any) => {
        setCurrent(row)
        setView(true)
    }

    const editRow = (row: any) => {
        router.visit(route("products.edit", { product: row.id }))
    }

    const deleteRow = (row: any) => {
        setDeleting(true)
        router.delete(route("products.destroy", row.id), {
            preserveScroll: true,
            onSuccess: () => setDeleted(true),
            onFinish: () => setDeleting(false),
        })
    }

    const sortBy = (sortField: string) => {
        const newFilters = { ...filters, sort: sortField }
        setFilters(newFilters)
        searchNow(newFilters)
    }

    const searchNow = (newFilters = filters) => {
        setSearching(true)
        router.get(route("products.index"), newFilters, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setSearching(false),
        })
    }

    const resetSearch = () => {
        const resetFilters = { store: "", trashed: "", sort: "" }
        setFilters(resetFilters)
        searchNow(resetFilters)
    }

    const can = (permission: string) => {
        // Replace with your actual permission checking logic
        return true
    }

    const t = (key: string, params?: any) => {
        // Replace with your actual translation function
        return key.replace(/[{}]/g, "").replace("x", params?.x || "")
    }

    const currency = (amount: number) => {
        return new Intl.NumberFormat("en-US", {
            style: "currency",
            currency: "USD",
        }).format(amount || 0)
    }

    const numberQty = (qty: number) => {
        return new Intl.NumberFormat().format(qty || 0)
    }

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Products',
            href: '/products',
        },
    ];

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
        // setSelectedUser(member);
        console.log('SelectedUserId: ' + member.id);
        console.log('SelectedUserName: ' + member.name);
        // setOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-2 md:py-2">
                        <TableActions
                            currentUser={auth.user}
                            authToken={auth.accessToken}
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
                                apiEndpoint="/api/v1/product/list/data" // Replace with your actual API endpoint
                                title={t('Workorder Management')}
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
    )
}
