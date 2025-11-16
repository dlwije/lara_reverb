// Pagination types
export interface PaginationLink {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
}

export interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

export interface ApiResponse<T> {
    products: T[];
    pagination: PaginationData;
    custom_fields: any[]; // You can define a more specific type for custom fields
    stores: Store[];
}
