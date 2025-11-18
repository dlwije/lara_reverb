// components/Layout/MobileTopBar.tsx
import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { Badge } from '@/components/ui/badge';
import {
    Search,
    Menu,
    MapPin,
    User,
    X,
    Phone,
    Mail,
    Globe,
} from 'lucide-react';
import ThemeToggle from '@/components/e-commerce/template/ThemeToggle';
import HeartIcon from '@/components/e-commerce/template/heartIcon';
import CartIcon from '@/components/e-commerce/public/cartIcon';
import { route } from 'ziggy-js';

interface MobileTopBarProps {
    currentAddress?: string;
    onSearch: (query: string) => void;
}

export function MobileTopBar({ currentAddress, onSearch }: MobileTopBarProps) {
    const [searchOpen, setSearchOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const { auth } = usePage().props;

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            onSearch(searchQuery);
            setSearchOpen(false);
        }
    };

    return (
        <div className="lg:hidden border-b border-border bg-background">
            {/* Top Info Bar - Mobile */}
            <div className="bg-primary/5 border-border border-b">
                <div className="px-4">
                    <div className="flex h-6 items-center justify-between text-xs">
                        <div className="text-muted-foreground flex items-center space-x-4 overflow-x-auto">
                            <div className="flex items-center space-x-1 whitespace-nowrap">
                                <Phone className="h-3 w-3" />
                                <span>+1 (555) 123-4567</span>
                            </div>
                            <div className="flex items-center space-x-1 whitespace-nowrap">
                                <Mail className="h-3 w-3" />
                                <span>support@example.com</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main Mobile Top Bar */}
            <div className="flex items-center justify-between h-14 px-4">
                {/* Left side - Menu and Location */}
                <div className="flex items-center space-x-3">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-9 w-9">
                                <Menu className="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="px-6 w-80 sm:w-96">
                            <div className="space-y-6 py-6">
                                {/* Mobile Menu Logo */}
                                <Link
                                    href={route('home')}
                                    className="text-foreground relative text-2xl font-semibold transition-opacity hover:opacity-80"
                                >
                                    <span className="text-primary">DS</span>
                                    <span className="text-green-500 dark:text-green-700">stack</span>
                                    <span className="text-primary text-3xl">.</span>
                                    <Badge className="text-primary-foreground absolute -right-8 -top-1 bg-green-500 px-2 py-0.5 text-xs font-semibold">
                                        plus
                                    </Badge>
                                </Link>

                                {/* Mobile Navigation */}
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <h3 className="font-semibold text-foreground">Shop</h3>
                                        <div className="space-y-1 pl-2">
                                            <Link href="/categories" className="block py-2 text-sm text-muted-foreground hover:text-foreground">
                                                All Categories
                                            </Link>
                                            <Link href="/deals" className="block py-2 text-sm text-muted-foreground hover:text-foreground">
                                                Today's Deals
                                            </Link>
                                            <Link href="/new-arrivals" className="block py-2 text-sm text-muted-foreground hover:text-foreground">
                                                New Arrivals
                                            </Link>
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <h3 className="font-semibold text-foreground">Account</h3>
                                        <div className="space-y-1 pl-2">
                                            {auth.user ? (
                                                <>
                                                    <Link href="/profile" className="block py-2 text-sm text-muted-foreground hover:text-foreground">
                                                        My Profile
                                                    </Link>
                                                    <Link href="/orders" className="block py-2 text-sm text-muted-foreground hover:text-foreground">
                                                        My Orders
                                                    </Link>
                                                    <Link href="/wishlist" className="block py-2 text-sm text-muted-foreground hover:text-foreground">
                                                        My Wishlist
                                                    </Link>
                                                    <Link
                                                        href="/logout"
                                                        method="post"
                                                        as="button"
                                                        className="block w-full text-left py-2 text-sm text-muted-foreground hover:text-foreground"
                                                    >
                                                        Logout
                                                    </Link>
                                                </>
                                            ) : (
                                                <Link href={route('login')} className="block py-2 text-sm text-muted-foreground hover:text-foreground">
                                                    Login / Register
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>

                    {/* Location */}
                    <Button variant="ghost" size="sm" className="h-8 gap-1 text-xs px-2">
                        <MapPin className="h-3 w-3" />
                        <span className="max-w-[60px] truncate">
              {currentAddress || 'Location'}
            </span>
                    </Button>
                </div>

                {/* Center - Logo */}
                <div className="flex-1 flex justify-center">
                    <Link
                        href={route('home')}
                        className="text-foreground relative text-xl font-semibold transition-opacity hover:opacity-80"
                    >
                        <span className="text-primary">DS</span>
                        <span className="text-green-500 dark:text-green-700">stack</span>
                        <span className="text-primary text-2xl">.</span>
                        <Badge className="text-primary-foreground absolute -right-6 -top-1 bg-green-500 px-1 py-0 text-[10px] font-semibold">
                            plus
                        </Badge>
                    </Link>
                </div>

                {/* Right side - Icons */}
                <div className="flex items-center space-x-1">
                    {/* Search */}
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-9 w-9"
                        onClick={() => setSearchOpen(true)}
                    >
                        <Search className="h-4 w-4" />
                    </Button>

                    {/* Theme Toggle */}
                    <ThemeToggle />

                    {/* User Account */}
                    {!auth.user && (
                        <Button variant="ghost" size="icon" className="h-9 w-9" asChild>
                            <Link href={route('login')}>
                                <User className="h-4 w-4" />
                            </Link>
                        </Button>
                    )}

                    {/* Wishlist */}
                    <div className="relative">
                        <HeartIcon />
                    </div>

                    {/* Cart */}
                    <div className="relative">
                        <CartIcon />
                    </div>
                </div>
            </div>

            {/* Search Overlay */}
            {searchOpen && (
                <div className="absolute top-0 left-0 right-0 z-50 bg-background border-b border-border p-4">
                    <form onSubmit={handleSearch} className="flex items-center space-x-2">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Search products..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-10 pr-10 h-9"
                                autoFocus
                            />
                            {searchQuery && (
                                <button
                                    type="button"
                                    onClick={() => setSearchQuery('')}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            )}
                        </div>
                        <Button type="submit" size="sm">
                            Search
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={() => setSearchOpen(false)}
                            className="h-9 w-9"
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    </form>
                </div>
            )}
        </div>
    );
}
