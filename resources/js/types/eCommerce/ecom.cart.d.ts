// Types based on your API response
export interface CartItem {
    rowId: string;
    id: number;
    name: string | null;
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

export interface CartData {
    content: CartItem[];
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
