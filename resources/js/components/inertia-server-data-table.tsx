'use client';

import {
    type ColumnDef,
    type ColumnFiltersState,
    type SortingState,
    type VisibilityState,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Filter,
    Plus,
    Search,
    StepBack,
    Trash2
} from 'lucide-react';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import apiClient from '@/lib/apiClient';
import { Link } from '@inertiajs/react';

interface Store {
    value: string | number;
    label: string;
}

interface ServerDataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    apiEndpoint: string;
    title?: string;
    searchPlaceholder?: string;
    searchColumn?: string;
    initialData?: any;
    filters?: Record<string, any>;
    useFilterFormat?: boolean; // Add a flag to use a filter format
    defaultPageSize?: number;
    stores?: Store[]; // Add stores prop
    selectedStore?: string | number | null; // Add a selected store prop
    onStoreChange?: (storeId: string | number | null) => void; // Add store change callback
    // Add trash filter props
    trashFilter?: boolean; // Enable/disable trash filtering
    selectedTrashStatus?: 'not' | 'with' | 'only' | null; // Updated to match backend params
    onTrashStatusChange?: (status: 'not' | 'with' | 'only' | null) => void;
}

interface ServerResponse<TData> {
    pagination: {
        current_page: number;
        data: TData[];
        total: number;
        per_page: number;
        last_page: number;
        from: number;
        to: number;
        first_page_url: string;
        last_page_url: string;
        next_page_url: string | null;
        prev_page_url: string | null;
        path: string;
        links: Array<{
            url: string | null;
            label: string;
            page: number | null;
            active: boolean;
        }>;
    };
    current_page?: number;
    data?: TData[];
    total?: number;
    per_page?: number;
    last_page?: number;
    draw?: number;
    recordsTotal?: number;
    recordsFiltered?: number;
    disableOrdering?: boolean;
}

export function InertiaServerDataTable<TData, TValue>({
    columns,
    apiEndpoint,
    title = 'Data Table',
    searchPlaceholder = 'Search...',
    searchColumn = 'name',
    initialData,
    filters = {},
    useFilterFormat = true, // Default to using filter format
    defaultPageSize = 10,
    stores = [], // Default empty array
    selectedStore = null, // Default no selection
    onStoreChange, // Callback function
    // Trash filter props
    trashFilter = false, // Default to disabled
    selectedTrashStatus = 'not', // Default to "not trashed"
    onTrashStatusChange,
}: ServerDataTableProps<TData, TValue>) {
    // Server state
    const [data, setData] = React.useState<TData[]>(initialData?.pagination?.data || initialData?.data || []);
    const [loading, setLoading] = React.useState(false);
    const [totalRecords, setTotalRecords] = React.useState(initialData?.pagination?.total || initialData?.total || 0);
    const [filteredRecords, setFilteredRecords] = React.useState(initialData?.pagination?.total || initialData?.total || 0);

    // Table state
    const [sorting, setSorting] = React.useState<SortingState>([]);
    const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([]);
    const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({});
    const [rowSelection, setRowSelection] = React.useState({});

    // Pagination state
    const [pageIndex, setPageIndex] = React.useState(initialData?.pagination?.current_page ? initialData.pagination.current_page - 1 : 0);
    const [pageSize, setPageSize] = React.useState(initialData?.pagination?.per_page || initialData?.per_page || 10);

    // Search state
    const [searchValue, setSearchValue] = React.useState('');
    const [debouncedSearch, setDebouncedSearch] = React.useState('');

    // Local state for store filter if not controlled from the parent
    const [localSelectedStore, setLocalSelectedStore] = React.useState<string | number | null>(selectedStore);

    // Local state for trash filter if not controlled from parent
    const [localTrashStatus, setLocalTrashStatus] = React.useState<'not' | 'with' | 'only' | null>(selectedTrashStatus);

    // Debounce search input
    React.useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchValue);
        }, 300);

        return () => clearTimeout(timer);
    }, [searchValue]);

    // Reset to the first page when search or filters change
    React.useEffect(() => {
        console.log('Filters changed, resetting page index');
        setPageIndex(0);
    }, [debouncedSearch, columnFilters, sorting, filters]);

    // Handle store change
    const handleStoreChange = (storeId: string | number | null) => {
        console.log('Handle store change:', storeId);
        if (onStoreChange) {
            console.log('Calling onStoreChange with:', storeId);
            onStoreChange(storeId);
            setLocalSelectedStore(storeId);
        } else {
            console.log('Setting local selected store:', storeId);
        }
    };

    // Use the appropriate store value
    const currentStore = localSelectedStore;
    console.log('Current store:', localSelectedStore);

    // Handle trash status change
    const handleTrashStatusChange = (status: 'not' | 'with' | 'only' | null) => {
        console.log('Handle trash status change:', status);
        if (onTrashStatusChange) {
            onTrashStatusChange(status);
        } else {
            setLocalTrashStatus(status);
        }
    };

    // Use the appropriate trash status value
    const currentTrashStatus = onTrashStatusChange ? selectedTrashStatus : localTrashStatus;

    // Build filter format parameters
    const buildFilterParams = React.useCallback(() => {
        const params = new URLSearchParams();

        // Add pagination parameters
        if (useFilterFormat) {
            params.append('filters[page]', (pageIndex + 1).toString());
            params.append('filters[per_page]', pageSize.toString());
        } else {
            params.append('page', (pageIndex + 1).toString());
            params.append('per_page', pageSize.toString());
            params.append('start', (pageIndex * pageSize).toString());
            params.append('length', pageSize.toString());
            params.append('draw', '1');
        }

        console.log('Building params with store:', currentStore);
        // Add store filter if selected
        if (currentStore) {
            if (useFilterFormat) {
                console.log('Using filter format');
                console.log('Current store:', currentStore);
                params.append('filters[store]', currentStore.toString());
            } else {
                params.append('store', currentStore.toString());
            }
        }

        // Add trash status filter if enabled and not default "not"
        if (trashFilter && currentTrashStatus && currentTrashStatus !== 'not') {
            if (useFilterFormat) {
                params.append('filters[trashed]', currentTrashStatus);
            } else {
                params.append('trashed', currentTrashStatus);
            }
        }

        // Add search parameter in filter format
        if (debouncedSearch) {
            if (useFilterFormat) {
                params.append('filters[search]', debouncedSearch);
                if (searchColumn) {
                    params.append('filters[search_column]', searchColumn);
                }
            } else {
                params.append('search', debouncedSearch);
                params.append('search[value]', debouncedSearch);
                params.append('search[regex]', 'false');
                if (searchColumn) {
                    params.append('search_column', searchColumn);
                }
            }
        }

        // Add sorting in filter format
        if (sorting.length > 0) {
            const sort = sorting[0];
            const sortValue = `${sort.id}:${sort.desc ? 'desc' : 'asc'}`;

            if (useFilterFormat) {
                params.append('filters[sort]', sortValue);
            } else {
                params.append('sort_by', sort.id);
                params.append('sort_direction', sort.desc ? 'desc' : 'asc');
                // Find column index for legacy format
                const backendColumns = columns.filter((column) => {
                    const columnId = column.id || ('accessorKey' in column ? column.accessorKey : undefined);
                    return columnId && !['select', 'actions', 'DT_RowIndex'].includes(columnId.toString());
                });
                const columnIndex = backendColumns.findIndex((col) => {
                    if (typeof col.id === 'string') return col.id === sort.id;
                    if ('accessorKey' in col && col.accessorKey) return col.accessorKey === sort.id;
                    return false;
                });
                if (columnIndex !== -1) {
                    params.append('order[0][column]', columnIndex.toString());
                    params.append('order[0][dir]', sort.desc ? 'desc' : 'asc');
                }
            }
        }

        // Add additional filters in filter format
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                if (useFilterFormat) {
                    params.append(`filters[${key}]`, value.toString());
                } else {
                    params.append(key, value.toString());
                }
            }
        });

        // Add column definitions (for server-side processing if needed)
        if (!useFilterFormat) {
            const backendColumns = columns.filter((column) => {
                const columnId = column.id || ('accessorKey' in column ? column.accessorKey : undefined);
                return columnId && !['select', 'actions', 'DT_RowIndex'].includes(columnId.toString());
            });

            backendColumns.forEach((column, index) => {
                const columnId = column.id || ('accessorKey' in column ? column.accessorKey : `column_${index}`);
                const columnName = ('accessorKey' in column ? column.accessorKey : columnId) || columnId;

                params.append(`columns[${index}][data]`, columnId.toString());
                params.append(`columns[${index}][name]`, columnName.toString());
                params.append(`columns[${index}][searchable]`, 'true');
                params.append(`columns[${index}][orderable]`, column.enableSorting !== false ? 'true' : 'false');
            });
        }

        return params;
    }, [
        pageIndex,
        pageSize,
        debouncedSearch,
        sorting,
        filters,
        columns,
        currentStore,
        currentTrashStatus,
        useFilterFormat,
        searchColumn,
        trashFilter,
    ]);

    // Fetch data from server
    const fetchData = React.useCallback(async () => {
        setLoading(true);
        try {
            const params = buildFilterParams();
            const url = `${apiEndpoint}?${params.toString()}`;

            console.log('Fetching from:', url); // For debugging

            const response = await apiClient.get(url);
            const result: ServerResponse<TData> = response.data;

            if (result.pagination) {
                setData(result.pagination.data);
                setTotalRecords(result.pagination.total);
                setFilteredRecords(result.pagination.total);
            } else if (result.current_page !== undefined) {
                setData(result.data || []);
                setTotalRecords(result.total || 0);
                setFilteredRecords(result.total || 0);
            } else {
                setData(result.data || []);
                setTotalRecords(result.recordsTotal || 0);
                setFilteredRecords(result.recordsFiltered || 0);
            }
        } catch (error) {
            console.error('Failed to fetch data:', error);
            setData([]);
            setTotalRecords(0);
            setFilteredRecords(0);
        } finally {
            setLoading(false);
        }
    }, [apiEndpoint, buildFilterParams]);

    // Fetch data when dependencies change
    React.useEffect(() => {
        fetchData();
    }, [fetchData]);

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
            if (typeof updater === 'function') {
                const newPagination = updater({ pageIndex, pageSize });
                setPageIndex(newPagination.pageIndex);
                setPageSize(newPagination.pageSize);
            }
        },
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        manualFiltering: true,
    });

    const totalPages = Math.ceil(filteredRecords / pageSize);
    const currentPage = pageIndex + 1;

    return (
        <Card className="me-2 ms-2 gap-0">
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardAction>
                    <div className="flex items-center gap-2">
                        {/* Combined Filters Row */}
                        <div className="flex items-center gap-1 rounded-md border p-1">
                            {/* Store Filter */}
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="sm" className="h-7 gap-1 px-2">
                                        <Filter className="h-3.5 w-3.5" />
                                        <span className="max-w-16 truncate text-xs">
                                            {currentStore ? stores.find((store) => store.value === currentStore)?.label : 'Store'}
                                        </span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-48">
                                    <DropdownMenuLabel>Filter by Store</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <div className="max-h-60 overflow-y-auto">
                                        {stores.map((store) => (
                                            <DropdownMenuCheckboxItem
                                                key={store.value}
                                                checked={currentStore === store.value}
                                                onCheckedChange={(checked) => {
                                                    if (checked) {
                                                        handleStoreChange(store.value);
                                                    } else {
                                                        handleStoreChange(null);
                                                    }
                                                }}
                                            >
                                                {store.label}
                                            </DropdownMenuCheckboxItem>
                                        ))}
                                    </div>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        onClick={() => handleStoreChange(null)}
                                        className="text-muted-foreground justify-center text-center"
                                    >
                                        Clear Filter
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            {/* Separator */}
                            <div className="bg-border h-4 w-px" />

                            {/* Status Filter */}
                            {trashFilter && (
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="sm" className="h-7 gap-1 px-2">
                                            <Trash2 className="h-3.5 w-3.5" />
                                            <span className="text-xs capitalize">
                                                {currentTrashStatus === 'not' ? 'Not' : currentTrashStatus === 'with' ? 'With' : 'Only'}
                                            </span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-48">
                                        <DropdownMenuLabel>Show Records</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuCheckboxItem
                                            checked={currentTrashStatus === 'not'}
                                            onCheckedChange={() => handleTrashStatusChange('not')}
                                        >
                                            Not Trashed
                                        </DropdownMenuCheckboxItem>
                                        <DropdownMenuCheckboxItem
                                            checked={currentTrashStatus === 'with'}
                                            onCheckedChange={() => handleTrashStatusChange('with')}
                                        >
                                            With Trashed
                                        </DropdownMenuCheckboxItem>
                                        <DropdownMenuCheckboxItem
                                            checked={currentTrashStatus === 'only'}
                                            onCheckedChange={() => handleTrashStatusChange('only')}
                                        >
                                            Only Trashed
                                        </DropdownMenuCheckboxItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            onClick={() => handleTrashStatusChange(null)}
                                            className="text-muted-foreground justify-center text-center"
                                        >
                                            Clear Filter
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            )}
                        </div>

                        {/* Add Product Button */}
                        <Link
                            href={route('admin.products.create')}
                            className="inline-flex h-8 items-center justify-center gap-1 whitespace-nowrap rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Plus className="h-4 w-4" />
                            {'Add'}
                        </Link>
                        {/*<Button variant="default" size="sm" className="h-8 gap-1">*/}
                        {/*    <Plus className="h-4 w-4" />*/}
                        {/*    Add*/}
                        {/*</Button>*/}
                    </div>
                </CardAction>
            </CardHeader>
            <CardContent>
                <div className="flex w-full items-center gap-3 py-4">
                    {/* Auto-expanding search */}
                    <div className="relative min-w-0 flex-1">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" />
                        <Input
                            aria-label={`Filter ${searchColumn}`}
                            placeholder={searchPlaceholder}
                            value={searchValue}
                            onChange={(e) => setSearchValue(e.target.value)}
                            className="h-10 w-full rounded-full border-zinc-200 bg-white/90 pl-9 pr-4 shadow-sm placeholder:text-zinc-500 focus-visible:border-transparent focus-visible:ring-2 focus-visible:ring-zinc-600 dark:border-zinc-800 dark:bg-zinc-900"
                        />
                    </div>

                    {/* Columns selector pinned to right */}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" className="ml-auto inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap bg-transparent">
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
                                        );
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
                                    <TableRow key={row.id} data-state={row.getIsSelected() && 'selected'}>
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
                            setPageSize(Number(value));
                            setPageIndex(0);
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
                            className="hidden h-8 w-8 bg-transparent p-0 lg:flex"
                            onClick={() => setPageIndex(0)}
                            disabled={pageIndex === 0}
                        >
                            <span className="sr-only">Go to first page</span>
                            <ChevronsLeft className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="h-8 w-8 bg-transparent p-0"
                            onClick={() => setPageIndex(pageIndex - 1)}
                            disabled={pageIndex === 0}
                        >
                            <span className="sr-only">Go to previous page</span>
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="h-8 w-8 bg-transparent p-0"
                            onClick={() => setPageIndex(pageIndex + 1)}
                            disabled={pageIndex >= totalPages - 1}
                        >
                            <span className="sr-only">Go to next page</span>
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="hidden h-8 w-8 bg-transparent p-0 lg:flex"
                            onClick={() => setPageIndex(totalPages - 1)}
                            disabled={pageIndex >= totalPages - 1}
                        >
                            <span className="sr-only">Go to last page</span>
                            <ChevronsRight className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div className="text-muted-foreground text-xs">
                    {filteredRecords > 0 && (
                        <>
                            Showing {pageIndex * pageSize + 1} to {Math.min((pageIndex + 1) * pageSize, filteredRecords)} of {filteredRecords} entries
                            {filteredRecords !== totalRecords && ` (filtered from ${totalRecords} total)`}
                        </>
                    )}
                </div>
            </CardFooter>
        </Card>
    );
}
