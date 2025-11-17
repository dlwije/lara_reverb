// types/index.ts
export interface Category {
    id: number;
    name: string;
    slug: string;
    photo?: string;
    order?: number;
    active: boolean;
    title?: string;
    description?: string;
    products_count?: number;
    children?: Category[];
}

export interface Brand {
    id: number;
    name: string;
    slug: string;
    photo?: string;
    order?: number;
    active: boolean;
    title?: string;
    description?: string;
    products_count?: number;
}

export interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string;
    type: string;
    price: number;
    cost: number;
    photo?: string;
    secondary_name?: string;
    description?: string;
    active: boolean;
    featured: boolean;
    on_sale: boolean;
    start_date?: string;
    end_date?: string;
    brand?: Brand;
    category?: Category;
    created_at: string;
    updated_at: string;
}

export interface Promotion {
    id: number;
    name: string;
    type: string;
    start_date?: string;
    end_date?: string;
    active: boolean;
    discount?: number;
    discount_method?: string;
    categories?: Category[];
}

export interface SaleItem {
    product_id: number;
    quantity: number;
    total: number;
    product?: Product;
}

export interface TopSellingProduct {
    product_id: number;
    total_quantity: number;
    total_revenue: number;
    product: Product;
}

export interface ApiResponse<T> {
    status: boolean;
    message: string;
    data: T;
}
