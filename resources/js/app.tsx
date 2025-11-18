import '../css/app.css';

import { CartProvider } from '@/contexts/CartContext';
import { WishListProvider } from '@/contexts/WishListContext';
import StoreProvider from '@/pages/e-commerce/storeProvider';
import { createInertiaApp } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import './lib/i18n';

configureEcho({
    broadcaster: 'reverb',
});

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <WishListProvider>
                <CartProvider>
                    <StoreProvider>
                        <App {...props} />
                    </StoreProvider>
                </CartProvider>
            </WishListProvider>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
