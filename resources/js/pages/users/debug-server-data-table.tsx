"use client"

import * as React from "react"
import {
    type ColumnDef,
    type ColumnFiltersState,
    type SortingState,
    type VisibilityState,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from "@tanstack/react-table"
import { ChevronDown, Search, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, AlertCircle } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Alert, AlertDescription } from "@/components/ui/alert"
import apiClient from "@/lib/apiClient"

interface ServerDataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[]
    apiEndpoint: string
    title?: string
    searchPlaceholder?: string
    searchColumn?: string
}

interface ServerResponse<TData> {
    draw: number
    recordsTotal: number
    recordsFiltered: number
    data: TData[]
    disableOrdering?: boolean
}

export function DebugServerDataTable<TData, TValue>({
                                                        columns,
                                                        apiEndpoint,
                                                        title = "Data Table",
                                                        searchPlaceholder = "Search...",
                                                        searchColumn = "email",
                                                    }: ServerDataTableProps<TData, TValue>) {
    // Server state
    const [data, setData] = React.useState<TData[]>([])
    const [loading, setLoading] = React.useState(false)
    const [totalRecords, setTotalRecords] = React.useState(0)
    const [filteredRecords, setFilteredRecords] = React.useState(0)
    const [error, setError] = React.useState<string | null>(null)
    const [debugInfo, setDebugInfo] = React.useState<any>(null)

    // Table state
    const [sorting, setSorting] = React.useState<SortingState>([])
    const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([])
    const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({})
    const [rowSelection, setRowSelection] = React.useState({})

    // Pagination state
    const [pageIndex, setPageIndex] = React.useState(0)
    const [pageSize, setPageSize] = React.useState(10)

    // Search state
    const [searchValue, setSearchValue] = React.useState("")
    const [debouncedSearch, setDebouncedSearch] = React.useState("")

    // Debounce search input
    React.useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchValue)
        }, 300)

        return () => clearTimeout(timer)
    }, [searchValue])

    // Reset to first page when search or filters change
    React.useEffect(() => {
        setPageIndex(0)
    }, [debouncedSearch, columnFilters, sorting])

    // Fetch data from server
    const fetchData = React.useCallback(async () => {
        console.log("🔄 Starting data fetch...")
        console.log("📍 API Endpoint:", apiEndpoint)
        console.log("📊 Current state:", { pageIndex, pageSize, debouncedSearch, sorting })

        setLoading(true)
        setError(null)

        try {
            const params = new URLSearchParams({
                start: (pageIndex * pageSize).toString(),
                length: pageSize.toString(),
                draw: "1",
            })

            const backendColumns = columns.filter((column) => {
                const columnId = column.id || ("accessorKey" in column ? column.accessorKey : undefined)
                return columnId !== "select" && columnId !== "DT_RowIndex" && columnId !== "actions"
            })

            console.log(
                "📋 Backend columns:",
                backendColumns.map((col) => ({
                    id: col.id,
                    accessorKey: "accessorKey" in col ? col.accessorKey : undefined,
                })),
            )

            backendColumns.forEach((column, index) => {
                const columnId = column.id || ("accessorKey" in column ? column.accessorKey : `column_${index}`)
                const columnName = ("accessorKey" in column ? column.accessorKey : columnId) || columnId

                params.append(`columns[${index}][data]`, columnId.toString())
                params.append(`columns[${index}][name]`, columnName.toString())
                params.append(`columns[${index}][searchable]`, "true")
                params.append(`columns[${index}][orderable]`, column.enableSorting !== false ? "true" : "false")
            })

            // Add search parameter
            if (debouncedSearch) {
                params.append("search[value]", debouncedSearch)
                params.append("search[regex]", "false")
            }

            if (sorting.length > 0) {
                const sort = sorting[0]
                const columnIndex = backendColumns.findIndex((col) => {
                    if (typeof col.id === "string") return col.id === sort.id
                    if ("accessorKey" in col && col.accessorKey) return col.accessorKey === sort.id
                    return false
                })

                if (columnIndex !== -1) {
                    params.append("order[0][column]", columnIndex.toString())
                    params.append("order[0][dir]", sort.desc ? "desc" : "asc")
                }
            }

            const fullUrl = `${apiEndpoint}?${params}`
            console.log("🌐 Full request URL:", fullUrl)
            console.log("📤 Request params:", Object.fromEntries(params))

            // Check if apiClient is properly configured
            console.log("🔧 API Client config:", {
                baseURL: apiClient.defaults.baseURL,
                timeout: apiClient.defaults.timeout,
                headers: apiClient.defaults.headers,
            })

            const response = await apiClient.get(`${apiEndpoint}?${params}`)

            console.log("📥 Response status:", response.status)
            console.log("📥 Response headers:", response.headers)
            console.log("📥 Response data:", response.data)

            // Validate response structure
            if (!response.data) {
                throw new Error("No data in response")
            }

            const result: ServerResponse<TData> = response.data

            // Check if response has expected structure
            if (
                typeof result.recordsTotal === "undefined" ||
                typeof result.recordsFiltered === "undefined" ||
                !Array.isArray(result.data)
            ) {
                console.warn("⚠️ Response doesn't match expected DataTables format:", result)
                setDebugInfo({
                    responseStructure: Object.keys(result),
                    expectedStructure: ["draw", "recordsTotal", "recordsFiltered", "data"],
                    actualData: result,
                })
            }

            setData(result.data || [])
            setTotalRecords(result.recordsTotal || 0)
            setFilteredRecords(result.recordsFiltered || 0)

            console.log("✅ Data loaded successfully:", {
                dataCount: result.data?.length || 0,
                totalRecords: result.recordsTotal,
                filteredRecords: result.recordsFiltered,
            })
        } catch (error: any) {
            console.error("❌ Failed to fetch data:", error)

            let errorMessage = "Failed to fetch data"

            if (error.response) {
                // Server responded with error status
                console.error("📥 Error response:", {
                    status: error.response.status,
                    statusText: error.response.statusText,
                    data: error.response.data,
                })
                errorMessage = `Server error: ${error.response.status} ${error.response.statusText}`
                if (error.response.data?.message) {
                    errorMessage += ` - ${error.response.data.message}`
                }
            } else if (error.request) {
                // Request was made but no response received
                console.error("📡 No response received:", error.request)
                errorMessage = "No response from server. Check if the API endpoint is accessible."
            } else {
                // Something else happened
                console.error("⚠️ Request setup error:", error.message)
                errorMessage = error.message
            }

            setError(errorMessage)
            setData([])
            setTotalRecords(0)
            setFilteredRecords(0)

            setDebugInfo({
                error: error.message,
                errorType: error.response ? "response" : error.request ? "request" : "setup",
                status: error.response?.status,
                statusText: error.response?.statusText,
                responseData: error.response?.data,
            })
        } finally {
            setLoading(false)
        }
    }, [apiEndpoint, pageIndex, pageSize, debouncedSearch, sorting, columns])

    // Fetch data when dependencies change
    React.useEffect(() => {
        console.log("🔄 Effect triggered - fetching data")
        fetchData()
    }, [fetchData])

    const table = useReactTable({
        data,
        columns,
        pageCount: Math.ceil(filteredRecords / pageSize),
        state: {
            sorting,
            columnFilters,
            columnVisibility,
            rowSelection,
            pagination: {
                pageIndex,
                pageSize,
            },
        },
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        onColumnVisibilityChange: setColumnVisibility,
        onRowSelectionChange: setRowSelection,
        onPaginationChange: (updater) => {
            if (typeof updater === "function") {
                const newPagination = updater({ pageIndex, pageSize })
                setPageIndex(newPagination.pageIndex)
                setPageSize(newPagination.pageSize)
            }
        },
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        manualFiltering: true,
    })

    const totalPages = Math.ceil(filteredRecords / pageSize)
    const currentPage = pageIndex + 1

    return (
        <Card className="ms-2 me-2 gap-0">
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                {error && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}
                {debugInfo && (
                    <Alert>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            <details>
                                <summary className="cursor-pointer font-medium">Debug Information</summary>
                                <pre className="mt-2 text-xs overflow-auto max-h-40">{JSON.stringify(debugInfo, null, 2)}</pre>
                            </details>
                        </AlertDescription>
                    </Alert>
                )}
            </CardHeader>
            <CardContent>
                <div className="flex w-full items-center gap-3 py-4">
                    <div className="relative min-w-0 flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-500" />
                        <Input
                            aria-label={`Filter ${searchColumn}`}
                            placeholder={searchPlaceholder}
                            value={searchValue}
                            onChange={(e) => setSearchValue(e.target.value)}
                            className="h-10 w-full rounded-full border-zinc-200 bg-white/90 pr-4 pl-9 shadow-sm placeholder:text-zinc-500 focus-visible:border-transparent focus-visible:ring-2 focus-visible:ring-zinc-600 dark:border-zinc-800 dark:bg-zinc-900"
                        />
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="outline"
                                className="ml-auto inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap bg-transparent"
                            >
                                <span>Columns</span>
                                <ChevronDown className="h-4 w-4 shrink-0" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            {table
                                .getAllColumns()
                                .filter((column) => column.getCanHide())
                                .map((column) => (
                                    <DropdownMenuCheckboxItem
                                        key={column.id}
                                        className="capitalize"
                                        checked={column.getIsVisible()}
                                        onCheckedChange={(value) => column.toggleVisibility(!!value)}
                                    >
                                        {column.id}
                                    </DropdownMenuCheckboxItem>
                                ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div className="overflow-hidden rounded-md border">
                    <Table>
                        <TableHeader>
                            {table.getHeaderGroups().map((headerGroup) => (
                                <TableRow key={headerGroup.id}>
                                    {headerGroup.headers.map((header) => {
                                        return (
                                            <TableHead key={header.id} className="min-w-[2rem] whitespace-nowrap">
                                                {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                                            </TableHead>
                                        )
                                    })}
                                </TableRow>
                            ))}
                        </TableHeader>
                        <TableBody>
                            {loading ? (
                                <TableRow>
                                    <TableCell colSpan={columns.length} className="h-24 text-center">
                                        <div className="flex items-center justify-center space-x-2">
                                            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-900"></div>
                                            <span>Loading...</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ) : error ? (
                                <TableRow>
                                    <TableCell colSpan={columns.length} className="h-24 text-center text-red-600">
                                        <div className="flex items-center justify-center space-x-2">
                                            <AlertCircle className="h-4 w-4" />
                                            <span>Failed to load data</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ) : table.getRowModel().rows?.length ? (
                                table.getRowModel().rows.map((row) => (
                                    <TableRow key={row.id} data-state={row.getIsSelected() && "selected"}>
                                        {row.getVisibleCells().map((cell) => (
                                            <TableCell key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</TableCell>
                                        ))}
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={columns.length} className="h-24 text-center">
                                        No results found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>

            <CardFooter className="flex items-center justify-between space-x-2 py-4">
                <div className="flex items-center space-x-2">
                    <p className="text-sm font-medium">Rows per page</p>
                    <Select
                        value={`${pageSize}`}
                        onValueChange={(value) => {
                            setPageSize(Number(value))
                            setPageIndex(0)
                        }}
                    >
                        <SelectTrigger className="h-8 w-[70px]">
                            <SelectValue placeholder={pageSize} />
                        </SelectTrigger>
                        <SelectContent side="top">
                            {[10, 20, 30, 40, 50].map((size) => (
                                <SelectItem key={size} value={`${size}`}>
                                    {size}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex items-center space-x-6 lg:space-x-8">
                    <div className="flex w-[100px] items-center justify-center text-sm font-medium">
                        Page {currentPage} of {totalPages || 1}
                    </div>
                    <div className="flex items-center space-x-2">
                        <Button
                            variant="outline"
                            className="hidden h-8 w-8 p-0 lg:flex bg-transparent"
                            onClick={() => setPageIndex(0)}
                            disabled={pageIndex === 0}
                        >
                            <span className="sr-only">Go to first page</span>
                            <ChevronsLeft className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="h-8 w-8 p-0 bg-transparent"
                            onClick={() => setPageIndex(pageIndex - 1)}
                            disabled={pageIndex === 0}
                        >
                            <span className="sr-only">Go to previous page</span>
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="h-8 w-8 p-0 bg-transparent"
                            onClick={() => setPageIndex(pageIndex + 1)}
                            disabled={pageIndex >= totalPages - 1}
                        >
                            <span className="sr-only">Go to next page</span>
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="hidden h-8 w-8 p-0 lg:flex bg-transparent"
                            onClick={() => setPageIndex(totalPages - 1)}
                            disabled={pageIndex >= totalPages - 1}
                        >
                            <span className="sr-only">Go to last page</span>
                            <ChevronsRight className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div className="text-xs text-muted-foreground">
                    {filteredRecords > 0 && (
                        <>
                            Showing {pageIndex * pageSize + 1} to {Math.min((pageIndex + 1) * pageSize, filteredRecords)} of{" "}
                            {filteredRecords} entries
                            {filteredRecords !== totalRecords && ` (filtered from ${totalRecords} total)`}
                        </>
                    )}
                </div>
            </CardFooter>
        </Card>
    )
}
