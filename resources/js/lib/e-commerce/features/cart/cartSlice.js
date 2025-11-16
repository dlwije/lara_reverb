import { createSlice } from '@reduxjs/toolkit'

const cartSlice = createSlice({
    name: 'cart',
    initialState: {
        total: 5,
        cartItems: {
            'prod_1':2, // productId: quantity
            'prod_2':5, // productId: quantity
            'prod_3':6, // productId: quantity
            'prod_4':3, // productId: quantity
            'prod_5':6, // productId: quantity

        }, // initially empty
    },
    reducers: {
        addToCart: (state, action) => {
            const { productId } = action.payload;
            if (state.cartItems[productId]) {
                state.cartItems[productId]++;
            } else {
                state.cartItems[productId] = 1;
            }
            state.total += 1;
        },
        removeFromCart: (state, action) => {
            const { productId } = action.payload;
            if (state.cartItems[productId]) {
                state.cartItems[productId]--;
                if (state.cartItems[productId] === 0) delete state.cartItems[productId];
                state.total -= 1;
            }
        },
        deleteItemFromCart: (state, action) => {
            const { productId } = action.payload;
            state.total -= state.cartItems[productId] || 0;
            delete state.cartItems[productId];
        },
    },
});

export const { addToCart, removeFromCart, clearCart, deleteItemFromCart } = cartSlice.actions

export default cartSlice.reducer
