import { CartData } from '@/types/eCommerce/ecom.cart';
import { createContext, useContext, useEffect, useReducer } from 'react';
import apiClient from '@/lib/apiClient';

interface CartState {
    cart: CartData;
    loading: boolean;
    error: string | null;
}

type CartAction =
    | { type: 'SET_LOADING'; payload: boolean }
    | { type: 'SET_CART'; payload: CartData }
    | { type: 'SET_ERROR'; payload: string }
    | { type: 'ADD_ITEM'; payload: CartData }
    | { type: 'UPDATE_ITEM'; payload: CartData }
    | { type: 'REMOVE_ITEM'; payload: CartData }
    | { type: 'CLEAR_CART'; payload: CartData };

interface CartContextType {
    cart: CartData;
    loading: boolean;
    error: string | null;
    getCart: () => Promise<void>;
    addToCart: (product: AddToCartProduct) => Promise<boolean>;
    updateQuantity: (rowId: string, qty: number) => Promise<boolean>;
    removeFromCart: (rowId: string) => Promise<boolean>;
    clearCart: () => Promise<boolean>;
    applyPromoCode: (promoCode: string) => Promise<boolean>;
}

interface AddToCartProduct {
    id: number;
    name: string;
    qty: number;
    price: string;
    options?: Record<string, any>; // This should be an object, not a string
}

// Initial state
const initialState: CartState = {
    cart: {
        content: [],
        count: 0,
        subtotal: "0.00",
        tax: "0.00",
        discount: "0.00",
        shipping: "0.00",
        total: "0.00",
        instance: "default",
        currency: "USD",
        identifier: ""
    },
    loading: false,
    error: null
};

// Reducer
const cartReducer = (state: CartState, action: CartAction): CartState => {
    switch (action.type) {
        case 'SET_LOADING':
            return { ...state, loading: action.payload };
        case 'SET_CART':
            return { ...state, cart: action.payload, error: null };
        case 'SET_ERROR':
            return { ...state, error: action.payload, loading: false };
        case 'ADD_ITEM':
        case 'UPDATE_ITEM':
        case 'REMOVE_ITEM':
        case 'CLEAR_CART':
            return { ...state, cart: action.payload, error: null };
        default:
            return state;
    }
};

// Create Context
const CartContext = createContext<CartContextType | undefined>(undefined);

export const CartProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [state, dispatch] = useReducer(cartReducer, initialState);

    // Initialize cart on mount
    useEffect(() => {
        getCart();
    }, []);

    const getCart = async () => {
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

    const addToCart = async (product: AddToCartProduct): Promise<boolean> => {
        dispatch({ type: 'SET_LOADING', payload: true });
        try {
            const payload = {
                product_id: product.id.toString(),
                name: product.name,
                qty: product.qty.toString(),
                price: product.price,
                ...(product.options && { options: product.options })
            };

            const response = await apiClient.post(route('cart.add'), payload);

            if (response.data.success) {
                dispatch({ type: 'ADD_ITEM', payload: response.data.data });
                return true;
            } else {
                dispatch({ type: 'SET_ERROR', payload: response.data.message || 'Failed to add to cart' });
                return false;
            }
        } catch (error) {
            dispatch({ type: 'SET_ERROR', payload: 'Error adding item to cart' });
            return false;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const updateQuantity = async (rowId: string, qty: number): Promise<boolean> => {
        if (qty < 0) return false;

        dispatch({ type: 'SET_LOADING', payload: true });
        try {
            const payload = {
                qty: qty
            }
            const params = new URLSearchParams({ qty: qty.toString() });
            const response = await apiClient.put(`/cart/update/${rowId}`, payload);

            if (response.data.success) {
                dispatch({ type: 'UPDATE_ITEM', payload: response.data.data });
                return true;
            } else {
                dispatch({ type: 'SET_ERROR', payload: 'Failed to update quantity' });
                return false;
            }
        } catch (error) {
            dispatch({ type: 'SET_ERROR', payload: 'Error updating quantity' });
            return false;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const removeFromCart = async (rowId: string): Promise<boolean> => {
        dispatch({ type: 'SET_LOADING', payload: true });
        try {
            const response = await apiClient.delete(`/cart/remove/${rowId}`);

            if (response.data.success) {
                dispatch({ type: 'REMOVE_ITEM', payload: response.data.data });
                return true;
            } else {
                dispatch({ type: 'SET_ERROR', payload: 'Failed to remove item' });
                return false;
            }
        } catch (error) {
            dispatch({ type: 'SET_ERROR', payload: 'Error removing item from cart' });
            return false;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const clearCart = async (): Promise<boolean> => {
        dispatch({ type: 'SET_LOADING', payload: true });
        try {
            const response = await apiClient.delete('/cart/clear');

            if (response.data.success) {
                dispatch({ type: 'CLEAR_CART', payload: response.data.data });
                return true;
            } else {
                dispatch({ type: 'SET_ERROR', payload: 'Failed to clear cart' });
                return false;
            }
        } catch (error) {
            dispatch({ type: 'SET_ERROR', payload: 'Error clearing cart' });
            return false;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const applyPromoCode = async (promoCode: string): Promise<boolean> => {
        dispatch({ type: 'SET_LOADING', payload: true });
        try {
            const params = new URLSearchParams({ promo_code: promoCode });
            const response = await apiClient.post(`/cart/apply-promo?${params}`);

            if (response.data.success) {
                dispatch({ type: 'UPDATE_ITEM', payload: response.data.data });
                return true;
            } else {
                dispatch({ type: 'SET_ERROR', payload: response.data.message || 'Failed to apply promo code' });
                return false;
            }
        } catch (error) {
            dispatch({ type: 'SET_ERROR', payload: 'Error applying promo code' });
            return false;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const value: CartContextType = {
        cart: state.cart,
        loading: state.loading,
        error: state.error,
        getCart,
        addToCart,
        updateQuantity,
        removeFromCart,
        clearCart,
        applyPromoCode
    };

    return (
        <CartContext.Provider value={value}>
            {children}
        </CartContext.Provider>
    );
};

// Hook
export const useCart = (): CartContextType => {
    const context = useContext(CartContext);
    if (context === undefined) {
        throw new Error('useCart must be used within a CartProvider');
    }
    return context;
};
