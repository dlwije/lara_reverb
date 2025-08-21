"use client"

import React, { useState, useEffect } from "react"
import { Head, router, usePage, route } from "@inertiajs/react"
import AdminLayout from "@/Layouts/AdminLayout"
import AutoComplete from "@/Components/AutoComplete"

// Mock components - replace with your actual components
const Header = ({ children, subheading, menu }: any) => (
    <div className="mb-6">
        <h1 className="text-2xl font-bold">{children}</h1>
        {subheading && <p className="text-gray-600 mt-1">{subheading}</p>}
        {menu && <div className="mt-4">{menu}</div>}
    </div>
)

const Button = ({ href, children, ...props }: any) => {
    if (href) {
        return (
            <a href={href} className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" {...props}>
                {children}
            </a>
        )
    }
    return (
        <button className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" {...props}>
            {children}
        </button>
    )
}

const Dropdown = ({ align, width, autoClose, children }: any) => {
    const [isOpen, setIsOpen] = useState(false)
    return (
        <div className="relative">
            {React.Children.map(children, (child) => {
                if (child.props.slot === "trigger") {
                    return React.cloneElement(child.props.children, {
                        onClick: () => setIsOpen(!isOpen),
                    })
                }
                if (child.props.slot === "content" && isOpen) {
                    return (
                        <div className="absolute right-0 mt-2 bg-white border rounded shadow-lg z-50">{child.props.children}</div>
                    )
                }
                return child
            })}
        </div>
    )
}

const DropdownLink = ({ href, children, ...props }: any) => (
    <a href={href} className="block px-4 py-2 text-sm hover:bg-gray-100" {...props}>
        {children}
    </a>
)

const Modal = ({ show, maxWidth, transparent, onClose, children }: any) => {
    if (!show) return null

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" onClick={onClose}>
            <div
                className={`bg-white rounded-lg p-6 ${maxWidth === "3xl" ? "max-w-3xl" : maxWidth === "2xl" ? "max-w-2xl" : "max-w-lg"} w-full mx-4`}
                onClick={(e) => e.stopPropagation()}
            >
                {children}
            </div>
        </div>
    )
}

const Loading = ({ circleSize }: any) => (
    <div className="flex justify-center items-center p-4">
        <div className={`animate-spin rounded-full border-b-2 border-blue-500 ${circleSize || "w-8 h-8"}`}></div>
    </div>
)

const Pagination = ({ meta, links, className }: any) => (
    <div className={`flex justify-between items-center ${className}`}>
        <div>
            Showing {meta?.from || 0} to {meta?.to || 0} of {meta?.total || 0} results
        </div>
        <div className="flex gap-2">
            {links?.map((link: any, index: number) => (
                <button
                    key={index}
                    onClick={() => link.url && router.visit(link.url)}
                    disabled={!link.url}
                    className={`px-3 py-1 rounded ${
                        link.active
                            ? "bg-blue-500 text-white"
                            : link.url
                                ? "bg-gray-200 hover:bg-gray-300"
                                : "bg-gray-100 text-gray-400 cursor-not-allowed"
                    }`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    </div>
)

const Icon = ({ name, size, className }: any) => (
    <span className={`inline-block ${size || "w-5 h-5"} ${className}`}>
    {/* Replace with your actual icon implementation */}📷
  </span>
)

const Actions = ({ row, record, editRow, deleted, deleting, deleteRow }: any) => (
    <div className="flex gap-2">
        <button onClick={() => editRow(row)} className="text-blue-500 hover:text-blue-700" title={`Edit ${record}`}>
            ✏️
        </button>
        <button
            onClick={() => deleteRow(row)}
            disabled={deleting}
            className="text-red-500 hover:text-red-700 disabled:opacity-50"
            title={`Delete ${record}`}
        >
            🗑️
        </button>
    </div>
)

const QuickView = ({ current, onClose, editRow }: any) => (
    <div>
        <div className="flex justify-between items-center mb-4">
            <h3 className="text-lg font-semibold">Product Details</h3>
            <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
                ✕
            </button>
        </div>
        {current && (
            <div className="space-y-2">
                <p>
                    <strong>Name:</strong> {current.name}
                </p>
                <p>
                    <strong>Code:</strong> {current.code}
                </p>
                <p>
                    <strong>Type:</strong> {current.type}
                </p>
                <p>
                    <strong>Brand:</strong> {current.brand?.name || ""}
                </p>
                <p>
                    <strong>Category:</strong> {current.category?.name || ""}
                </p>
                <div className="flex gap-2 mt-4">
                    <Button onClick={() => editRow(current)}>Edit</Button>
                    <Button onClick={onClose}>Close</Button>
                </div>
            </div>
        )}
    </div>
)

interface ProductsIndexProps {
    pagination: any
    taxes: any[]
    selected_store: string | null
    stores: any[]
}

const ProductsIndex: React.FC<ProductsIndexProps> = ({ pagination, taxes, selected_store, stores }) => {
    const { props } = usePage<any>()
    const [view, setView] = useState(false)
    const [photo, setPhoto] = useState<string | null>(null)
    const [current, setCurrent] = useState<any>(null)
    const [deleted, setDeleted] = useState(false)
    const [deleting, setDeleting] = useState(false)
    const [searching, setSearching] = useState(false)

    const [filters, setFilters] = useState({
        store: selected_store || props.filters?.store || "",
        trashed: props.filters?.trashed || "",
        sort: props.filters?.sort || "",
    })

    useEffect(() => {
        if (selected_store) {
            setFilters((prev) => ({
                ...prev,
                store: props.filters?.store || selected_store,
            }))
        }
    }, [selected_store, props.filters])

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

    return (
        <AdminLayout>
            <Head>
                <title>{t("Products")}</title>
            </Head>

            <Header
                subheading={t("Please review the data below")}
                menu={
                    <div className="flex items-center justify-center gap-4">
                        {can("create-products") && (
                            <Button href={route("products.create")}>{t("Add {x}", { x: t("Product") })}</Button>
                        )}

                        {(can("import-products") || can("export-products")) && (
                            <Dropdown align="right" width="40" autoClose={false}>
                                <div slot="trigger">
                                    <button className="flex items-center -m-2 p-2.5 rounded-md transition duration-150 ease-in-out">
                                        <Icon name="v-arrows" size="size-6" />
                                        <span className="sr-only">{t("Import/Export")}</span>
                                    </button>
                                </div>
                                <div slot="content">
                                    <div className="block px-4 py-2 text-xs text-gray-400">{t("Import/Export")}</div>
                                    {can("import-products") && (
                                        <DropdownLink href={route("products.import")}>{t("Import {x}", { x: t("Products") })}</DropdownLink>
                                    )}
                                    {can("export-products") && (
                                        <DropdownLink href={route("products.export")}>{t("Export {x}", { x: t("Products") })}</DropdownLink>
                                    )}
                                </div>
                            </Dropdown>
                        )}

                        <Dropdown align="right" width="56" autoClose={false}>
                            <div slot="trigger">
                                <button className="flex items-center -m-2 p-2.5 rounded-md transition duration-150 ease-in-out">
                                    <Icon name="funnel-o" size="size-5" />
                                    <span className="sr-only">{t("Show Filters")}</span>
                                </button>
                            </div>
                            <div slot="content">
                                <div className="px-4 py-2">
                                    <div className="mb-4">
                                        <AutoComplete
                                            endpoint="/api/stores"
                                            placeholder={t("Store")}
                                            defaultValue={filters.store}
                                            onSelect={(option: any) => {
                                                const newFilters = { ...filters, store: option?.value || "" }
                                                setFilters(newFilters)
                                                searchNow(newFilters)
                                            }}
                                        />
                                    </div>
                                    <div>
                                        <AutoComplete
                                            endpoint="/api/trashed-options"
                                            placeholder={t("With Trashed")}
                                            defaultValue={filters.trashed}
                                            onSelect={(option: any) => {
                                                const newFilters = { ...filters, trashed: option?.value || "" }
                                                setFilters(newFilters)
                                                searchNow(newFilters)
                                            }}
                                        />
                                    </div>
                                </div>
                            </div>
                        </Dropdown>
                    </div>
                }
            >
                {t("Products")}
            </Header>

            <div className="relative px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-800 grow self-stretch flex flex-col items-stretch justify-stretch">
                {searching && <Loading circleSize="w-10 h-10" />}

                <div className="flow-root grow">
                    <div className="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div className="inline-block min-w-full my-2 align-middle border-b border-gray-200 dark:border-gray-700">
                            <table className="fixed-actions min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                <tr>
                                    <th
                                        scope="col"
                                        className="py-3.5 pl-4 pr-3 text-center text-sm font-semibold text-focus sm:pl-6 lg:pl-8 w-16"
                                    >
                                        <span className="sr-only">{t("Photo")}</span>
                                        <Icon name="photo" size="size-5 m-auto" />
                                    </th>
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        <button
                                            type="button"
                                            className="flex items-center gap-2 whitespace-nowrap"
                                            onClick={() => sortBy(filters?.sort === "name:asc" ? "name:desc" : "name:asc")}
                                        >
                                            {t("Name")}
                                            {filters?.sort?.startsWith("name:") && (
                                                <Icon size="size-3 text-mute" name={filters.sort === "name:desc" ? "c-up" : "c-down"} />
                                            )}
                                        </button>
                                    </th>
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        <button
                                            type="button"
                                            className="flex items-center gap-2 whitespace-nowrap"
                                            onClick={() => sortBy(filters?.sort === "code:asc" ? "code:desc" : "code:asc")}
                                        >
                                            {t("Code")}
                                            {filters?.sort?.startsWith("code:") && (
                                                <Icon size="size-3 text-mute" name={filters.sort === "code:desc" ? "c-up" : "c-down"} />
                                            )}
                                        </button>
                                    </th>
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        <button
                                            type="button"
                                            className="flex items-center gap-2 whitespace-nowrap"
                                            onClick={() => sortBy(filters?.sort === "type:asc" ? "type:desc" : "type:asc")}
                                        >
                                            {t("Type")}
                                            {filters?.sort?.startsWith("type:") && (
                                                <Icon size="size-3 text-mute" name={filters.sort === "type:desc" ? "c-up" : "c-down"} />
                                            )}
                                        </button>
                                    </th>
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        <button
                                            type="button"
                                            className="flex items-center gap-2 whitespace-nowrap"
                                            onClick={() =>
                                                sortBy(filters?.sort === "brand.name:asc" ? "brand.name:desc" : "brand.name:asc")
                                            }
                                        >
                                            {t("Brand")}
                                            {filters?.sort?.startsWith("brand.name:") && (
                                                <Icon size="size-3 text-mute" name={filters.sort === "brand.name:desc" ? "c-up" : "c-down"} />
                                            )}
                                        </button>
                                    </th>
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        <button
                                            type="button"
                                            className="flex items-center gap-2 whitespace-nowrap"
                                            onClick={() =>
                                                sortBy(filters?.sort === "category.name:asc" ? "category.name:desc" : "category.name:asc")
                                            }
                                        >
                                            {t("Category")}
                                            {filters?.sort?.startsWith("category.name:") && (
                                                <Icon
                                                    size="size-3 text-mute"
                                                    name={filters.sort === "category.name:desc" ? "c-up" : "c-down"}
                                                />
                                            )}
                                        </button>
                                    </th>
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        <button
                                            type="button"
                                            className="flex items-center gap-2 whitespace-nowrap"
                                            onClick={() =>
                                                sortBy(
                                                    filters?.sort === "supplier.company:asc" ? "supplier.company:desc" : "supplier.company:asc",
                                                )
                                            }
                                        >
                                            {t("Supplier")}
                                            {filters?.sort?.startsWith("supplier.company:") && (
                                                <Icon
                                                    size="size-3 text-mute"
                                                    name={filters.sort === "supplier.company:desc" ? "c-up" : "c-down"}
                                                />
                                            )}
                                        </button>
                                    </th>
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        {t("Quantity")}
                                    </th>
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        {t("Unit")}
                                    </th>
                                    {can("show-cost") && (
                                        <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                            <button
                                                type="button"
                                                className="flex items-center gap-2 whitespace-nowrap"
                                                onClick={() => sortBy(filters?.sort === "cost:asc" ? "cost:desc" : "cost:asc")}
                                            >
                                                {t("Cost")}
                                                {filters?.sort?.startsWith("cost:") && (
                                                    <Icon size="size-3 text-mute" name={filters.sort === "cost:desc" ? "c-up" : "c-down"} />
                                                )}
                                            </button>
                                        </th>
                                    )}
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        <button
                                            type="button"
                                            className="flex items-center gap-2 whitespace-nowrap"
                                            onClick={() => sortBy(filters?.sort === "price:asc" ? "price:desc" : "price:asc")}
                                        >
                                            {t("Price")}
                                            {filters?.sort?.startsWith("price:") && (
                                                <Icon size="size-3 text-mute" name={filters.sort === "price:desc" ? "c-up" : "c-down"} />
                                            )}
                                        </button>
                                    </th>
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        {t("Taxes")}
                                    </th>
                                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-focus">
                                        <button
                                            type="button"
                                            className="flex items-center gap-2 whitespace-nowrap"
                                            onClick={() =>
                                                sortBy(filters?.sort === "rack_location:asc" ? "rack_location:desc" : "rack_location:asc")
                                            }
                                        >
                                            {t("Rack Location")}
                                            {filters?.sort?.startsWith("rack_location:") && (
                                                <Icon
                                                    size="size-3 text-mute"
                                                    name={filters.sort === "rack_location:desc" ? "c-up" : "c-down"}
                                                />
                                            )}
                                        </button>
                                    </th>
                                    <th scope="col" className="relative py-3.5 pl-3 pr-4 sm:pr-6 lg:pr-8 w-16">
                                        <span className="sr-only">{t("Actions")}</span>
                                    </th>
                                </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                {pagination && pagination.data && pagination.data.length ? (
                                    pagination.data.map((row: any) => (
                                        <tr key={row.id}>
                                            <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-focus sm:pl-6 lg:pl-8 w-14">
                                                {row.photo && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setPhoto(row.photo)}
                                                        className="-my-4 w-8 h-8 flex items-center justify-center"
                                                    >
                                                        <img
                                                            alt=""
                                                            src={row.photo || "/placeholder.svg"}
                                                            className="rounded-sm max-w-full max-h-full"
                                                        />
                                                    </button>
                                                )}
                                            </td>
                                            <td
                                                onClick={() => viewRow(row)}
                                                className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm font-semibold"
                                            >
                                                {row.name}
                                            </td>
                                            <td onClick={() => viewRow(row)} className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                                                {row.code}
                                            </td>
                                            <td onClick={() => viewRow(row)} className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                                                {row.type}
                                            </td>
                                            <td onClick={() => viewRow(row)} className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                                                {row.brand?.name || ""}
                                            </td>
                                            <td onClick={() => viewRow(row)} className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                                                {row.category?.name}
                                            </td>
                                            <td onClick={() => viewRow(row)} className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                                                {row.supplier?.company || row.supplier?.name || ""}
                                            </td>
                                            <td
                                                onClick={() => viewRow(row)}
                                                className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm text-right"
                                            >
                                                {row.dont_track_stock || row.type !== "Standard"
                                                    ? ""
                                                    : numberQty(
                                                        selected_store || filters.store
                                                            ? row.stocks?.find((s: any) => s.store_id === filters.store || selected_store)
                                                                ?.balance
                                                            : row.stocks?.reduce((a: number, s: any) => Number(s.balance) + a, 0),
                                                    )}
                                            </td>
                                            <td onClick={() => viewRow(row)} className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                                                {row.unit?.name || ""}
                                            </td>
                                            {can("show-cost") && (
                                                <td
                                                    onClick={() => viewRow(row)}
                                                    className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm text-right"
                                                >
                                                    {currency(row.cost)}
                                                </td>
                                            )}
                                            <td
                                                onClick={() => viewRow(row)}
                                                className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm text-right"
                                            >
                                                {currency(
                                                    selected_store
                                                        ? row.stores?.find((s: any) => s.id === selected_store || filters.store)?.pivot?.price ||
                                                        row.price
                                                        : row.price,
                                                )}
                                            </td>
                                            <td onClick={() => viewRow(row)} className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                                                {selected_store || filters.store
                                                    ? row.stores
                                                    ?.find((s: any) => s.id === selected_store || filters.store)
                                                    ?.pivot?.taxes?.taxes?.map((t: any) => t.name)
                                                    ?.join(", ") || row.taxes?.map((t: any) => t.name)?.join(", ")
                                                    : row.taxes?.map((t: any) => t.name)?.join(", ") || ""}
                                            </td>
                                            <td onClick={() => viewRow(row)} className="cursor-pointer whitespace-nowrap px-3 py-4 text-sm">
                                                {row.rack_location || ""}
                                            </td>
                                            <td className="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 lg:pr-8 w-16">
                                                <div className="flex items-center justify-end gap-4 text-mute">
                                                    {!row.dont_track_stock && row.type === "Standard" && (
                                                        <a className="link" href={route("products.track", { product: row.id })}>
                                                            <Icon name="d-arrows" size="size-5" />
                                                        </a>
                                                    )}
                                                    <Actions
                                                        row={row}
                                                        record={t("Product")}
                                                        editRow={editRow}
                                                        deleted={deleted}
                                                        deleting={deleting}
                                                        deleteRow={deleteRow}
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={100}>
                                            <div className="whitespace-nowrap pl-4 pr-3 py-3.5 text-sm font-light text-mute sm:pl-2 lg:pl-4">
                                                {t("There is no data to display!")}
                                            </div>
                                        </td>
                                    </tr>
                                )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div className="-mx-4 sm:-mx-6 lg:-mx-8">
                    <Pagination className="mt-auto mx-4 sm:mx-6 py-2 text-sm" meta={pagination.meta} links={pagination.links} />
                </div>

                <Modal show={view} maxWidth="3xl" onClose={() => setView(false)}>
                    <QuickView current={current} onClose={() => setView(false)} editRow={editRow} />
                </Modal>

                <Modal show={!!photo} maxWidth="2xl" transparent={true} onClose={() => setPhoto(null)}>
                    <div className="flex items-center justify-center">
                        <img alt="" src={photo || ""} className="rounded-md w-full h-full max-w-full min-h-24 max-h-screen" />
                    </div>
                </Modal>
            </div>
        </AdminLayout>
    )
}

ProductsIndex.layout = (page: React.ReactElement) => <AdminLayout>{page}</AdminLayout>

export default ProductsIndex
