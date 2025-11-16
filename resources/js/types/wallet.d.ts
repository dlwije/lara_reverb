// types/wallet.ts
export interface Transaction {
    id: number;
    description: string;
    direction: 'CR' | 'DR'; // Credit or Debit
    amount: string;
    currency: string;
    currency_symble: string;
    payment_type: string;
    payment_method: string;
    status: string;
    ref_id: string | null;
    created_at: string;
    updated_at: string;
    running_balance: number;
    formatted_amount: string;
    formatted_running_balance: string;
}

export interface PaginatedResponse<T> {
    status: boolean;
    message: string;
    data: {
        current_page: number;
        data: T[];
        first_page_url: string;
        from: number | null;
        last_page: number;
        last_page_url: string;
        links: Array<{
            url: string | null;
            label: string;
            page: number | null;
            active: boolean;
        }>;
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number | null;
        total: number;
    };
}

export interface TransactionGroup {
    date: string;
    balance: string;
    transactions: Transaction[];
}

export interface ServerResponse<T> {
    data: T;
    success: boolean;
    message?: string;
}

export interface WalletFilters {
    period?: string;
    type?: string;
    page?: number;
    limit?: number;
}
