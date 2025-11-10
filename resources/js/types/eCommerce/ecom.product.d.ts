// Supplier types
export interface Supplier {
    id: number;
    name: string;
    company: string;
    balance: number;
    state: string | null;
    country: string | null;
}

// Category types
export interface Category {
    id: number;
    name: string;
    category_id: number | null;
    children: any[]; // You can define a more specific type if needed
}

// Product base interface
export interface Product {
    id: number;
    sku: string;
    type: string;
    code: string;
    name: string;
    slug: string;
    title: string | null;
    description: string | null;
    keywords: string | null;
    noindex: string | null;
    nofollow: string | null;
    price: string;
    active: string | null;
    rack: string | null;
    photo: string | null;
    video_url: string | null;
    details: string;
    secondary_name: string | null;
    symbology: string;
    min_price: string | null;
    max_price: string | null;
    max_discount: string | null;
    dont_track_stock: number;
    changeable: number;
    alert_quantity: string;
    extra_attributes: any[]; // You can define a more specific type
    weight: string | null;
    supplier_id: number;
    category_id: number;
    subcategory_id: number | null;
    supplier_part_id: number | null;
    rack_location: string | null;
    dimensions: string | null;
    hsn_number: string | null;
    sac_number: string | null;
    featured: string | null;
    hide_in_pos: string | null;
    hide_in_shop: string | null;
    tax_included: string | null;
    can_edit_price: string | null;
    can_edit_taxes: string | null;
    has_expiry_date: string | null;
    has_serials: string | null;
    on_sale: string | null;
    start_date: string | null;
    end_date: string | null;
    has_variants: number;
    variants: string | null;
    file: string | null;
    brand_id: number | null;
    unit_id: number | null;
    sale_unit_id: number | null;
    purchase_unit_id: number | null;
    company_id: number;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    taxes: any[]; // You can define a more specific type for taxes
    supplier: Supplier;
    stocks: any[]; // You can define a more specific type for stocks
    brand: any | null; // You can define a more specific type for brand
    category: Category;
    unit: any | null; // You can define a more specific type for unit
}

// Store type
export interface Store {
    value: number;
    label: string;
}

// Wrapper type for the nested product structure
export interface ProductWrapper {
    "Modules\\Product\\Models\\Product": Product;
}

// Main response type
export interface ProductListResponse {
    products: Product[];
    pagination: PaginationData;
    custom_fields: any[];
    stores: Store[];
}
