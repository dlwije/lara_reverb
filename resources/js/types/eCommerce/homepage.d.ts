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

export interface Address {
    id: number;
    name: string;
    street: string;
    city: string;
    state: string;
    zip_code: string;
    country: string;
    is_default: boolean;
}

export interface Language {
    code: string;
    name: string;
    native_name: string;
    flag: string;
}

export interface WishlistItem {
    rowId: string;
    id: number;
    name: string | null;
    slug: string | null;
    qty: number;
    price: string;
    taxRate: string;
    taxAmount: string;
    discountAmount: string;
    subtotal: string;
    total: string;
    options: {
        size?: string;
        color?: string;
        [key: string]: any;
    };
    productAttributes: any[];
    product: any;
}

export interface WishList {
    content: WishlistItem[];
    count: number;
    subtotal: string;
    tax: string;
    discount: string;
    shipping: string;
    total: string;
    instance: string;
    currency: string;
    identifier: string;
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
