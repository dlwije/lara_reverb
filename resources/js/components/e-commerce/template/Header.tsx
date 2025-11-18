// components/Layout/Header.tsx
import { useState } from 'react';
import { TopBar } from '@/components/e-commerce/template/TopBar';
import { MobileTopBar } from '@/components/e-commerce/template/MobileTopBar';
import { MainNavigation } from '@/components/e-commerce/template/MainNavigation';


export function Header() {
    const [searchQuery, setSearchQuery] = useState('');

    const handleSearch = (query: string) => {
        setSearchQuery(query);
        // Navigate to search page or filter products
        window.location.href = `/search?q=${encodeURIComponent(query)}`;
    };

    return (
        <header className="sticky top-0 z-50 w-full bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div className="hidden lg:block">
                <TopBar />
            </div>
            <div className="lg:hidden">
                <MobileTopBar
                    cartItemCount={0} // You would pass actual counts
                    wishlistItemCount={0}
                    onSearch={handleSearch}
                />
            </div>
            <MainNavigation />
        </header>
    );
}
