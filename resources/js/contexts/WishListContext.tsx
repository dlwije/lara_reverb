import { WishList } from '@/types/eCommerce/homepage';
import { createContext, useContext, useEffect, useReducer } from 'react';
import apiClient from '@/lib/apiClient';

interface WishListState {
    wishlist: WishList;
    loading: boolean;
    error: string | null;
}

type WishListAction =
    | { type: 'SET_LOADING'; payload: boolean }
    | { type: 'SET_WISHLIST'; payload: WishList }
    | { type: 'SET_ERROR'; payload: string }
    | { type: 'ADD_ITEM'; payload: WishList }
    | { type: 'UPDATE_ITEM'; payload: WishList }
    | { type: 'REMOVE_ITEM'; payload: WishList }
    | { type: 'CLEAR_WISHLIST'; payload: WishList};

interface WishListContextType {
    wishlist: WishList;
    loading: boolean;
    error: string | null;
    getWishList: () => Promise<void>;
    addToWishList: (product: AddToWishListProduct) => Promise<{success: boolean; message?: string}>;
    updateQuantity: (rowId: string, qty: number) => Promise<{success: boolean; message?: string}>;
    removeFromWishList: (rowId: string) => Promise<{success: boolean; message?: string}>;
    clearWishList: () => Promise<{success: boolean; message?: string}>;
    applyPromoCode: (promoCode: string) => Promise<{success: boolean; message?: string}>;
}

interface AddToWishListProduct {
    id: number;
    name: string;
    qty: number;
    price: string;
    options?: Record<string, any>; // This should be an object, not a string
}

// Initial state
const initialState: WishListState = {
    wishlist: {
        content: [],
        count: 0,
        subtotal: "0.00",
        tax: "0.00",
        discount: "0.00",
        shipping: "0.00",
        total: "0.00",
        instance: "default",
        currency: "AED",
        identifier: ""
    },
    loading: false,
    error: null
};

const wishListReducer = (state: WishListState, action: WishListAction): WishListState => {
    switch (action.type) {
        case 'SET_LOADING':
            return { ...state, loading: action.payload };
        case 'SET_WISHLIST':
            return { ...state, wishlist: action.payload, error: null };
        case 'SET_ERROR':
            return { ...state, error: action.payload, loading: false };
        case 'ADD_ITEM':
        case 'UPDATE_ITEM':
        case 'REMOVE_ITEM':
        case 'CLEAR_WISHLIST':
            return { ...state, wishlist: action.payload, error: null };
        default:
            return state;
    }
};

const WishListContext = createContext<WishListContextType | undefined>(undefined);

export const WishListProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [state, dispatch] = useReducer(wishListReducer, initialState);

    // Initialize cart on mount
    useEffect(() => {
        getWishList();
    }, []);

    const getWishList = async () => {
        dispatch({ type: 'SET_LOADING', payload: true });
        try {
            const response = await apiClient.get('/cart/data');
            if (response.data.success) {
                dispatch({ type: 'SET_CART', payload: response.data.data });
            } else {
                dispatch({ type: 'SET_ERROR', payload: 'Failed to fetch cart' });
            }
        } catch (error) {
            dispatch({ type: 'SET_ERROR', payload: 'Error fetching cart data' });
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const value: WishListContextType = {
        wishlist: state.wishlist,
        loading: state.loading,
        error: state.error
    }

    return <WishListContext.Provider value={value}>{children}</WishListContext.Provider>;
}

export const useWishList = (): WishListContextType => {
    const context = useContext(WishListContext);
    if(context === undefined) {
        throw new Error('useWishList must be used within a WishListProvider');
    }
    return context;
};
