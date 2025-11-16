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
import { ChevronDown, Search, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from "lucide-react"

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
import apiClient from '@/lib/apiClient';

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

export function ServerDataTable<TData, TValue>({
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
        setLoading(true)
        try {
            const params = new URLSearchParams({
                start: (pageIndex * pageSize).toString(),
                length: pageSize.toString(),
                draw: "1",
            })

            const backendColumns = columns.filter((column) => {
                // Safely access column properties
                const columnId = column.id || ("accessorKey" in column ? column.accessorKey : undefined)
                // Exclude select checkbox column and row index column
                return columnId !== "select" && columnId !== "DT_RowIndex" && columnId !== "actions"
            })

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
        // Find the column index by matching the column ID
        const columnIndex = backendColumns.findIndex((col) => {
          // Handle both string IDs and accessor functions
          if (typeof col.id === "string") return col.id === sort.id
            if ("accessorKey" in col && col.accessorKey) return col.accessorKey === sort.id
          return false
        })

        if (columnIndex !== -1) {
          params.append("order[0][column]", columnIndex.toString())
          params.append("order[0][dir]", sort.desc ? "desc" : "asc")
        }
      }

            const response = await apiClient.get(`${apiEndpoint}?${params}`)
            const result: ServerResponse<TData> = response.data

            setData(result.data)
            setTotalRecords(result.recordsTotal)
            setFilteredRecords(result.recordsFiltered)
        } catch (error) {
            console.error("Failed to fetch data:", error)
            setData([])
            setTotalRecords(0)
            setFilteredRecords(0)
        } finally {
            setLoading(false)
        }
    }, [apiEndpoint, pageIndex, pageSize, debouncedSearch, sorting, columns])

    // Fetch data when dependencies change
    React.useEffect(() => {
        console.log('fetching data');
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
            </CardHeader>
            <CardContent>
                <div className="flex w-full items-center gap-3 py-4">
                    {/* Auto-expanding search */}
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

                    {/* Columns selector pinned to right */}
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
                                        Loading...
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
                                        No results.
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
                        Page {currentPage} of {totalPages}
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
