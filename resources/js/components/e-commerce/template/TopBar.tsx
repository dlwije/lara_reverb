// components/Layout/TopBar.tsx (Fixed)
import HeartIcon from '@/components/e-commerce/template/heartIcon';
import CartIcon from '@/components/e-commerce/public/cartIcon';
import ThemeToggle from '@/components/e-commerce/template/ThemeToggle';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuList,
    NavigationMenuTrigger,
} from '@/components/ui/navigation-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { Address, Language } from '@/types/eCommerce/homepage';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Globe, Mail, MapPin, Phone, Search, User, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { route } from 'ziggy-js';

export function TopBar() {
    const [currentAddress, setCurrentAddress] = useState<Address | null>(null);
    const [addresses, setAddresses] = useState<Address[]>([]);
    const [languages, setLanguages] = useState<Language[]>([]);
    const [currentLanguage, setCurrentLanguage] = useState<Language | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [loading, setLoading] = useState(true);

    const { auth } = usePage().props;

    useEffect(() => {
        loadInitialData();
    }, []);

    const loadInitialData = async () => {
        try {
            const mockAddresses: Address[] = [
                {
                    id: 1,
                    name: 'Home',
                    street: '123 Main Street',
                    city: 'New York',
                    state: 'NY',
                    zip_code: '10001',
                    country: 'United States',
                    is_default: true,
                },
                {
                    id: 2,
                    name: 'Work',
                    street: '456 Office Blvd',
                    city: 'New York',
                    state: 'NY',
                    zip_code: '10002',
                    country: 'United States',
                    is_default: false,
                },
            ];

            const mockLanguages: Language[] = [
                { code: 'en', name: 'English', native_name: 'English', flag: '🇺🇸' },
                { code: 'es', name: 'Spanish', native_name: 'Español', flag: '🇪🇸' },
                { code: 'fr', name: 'French', native_name: 'Français', flag: '🇫🇷' },
                { code: 'de', name: 'German', native_name: 'Deutsch', flag: '🇩🇪' },
            ];

            setAddresses(mockAddresses);
            setCurrentAddress(mockAddresses.find((addr) => addr.is_default) || mockAddresses[0]);
            setLanguages(mockLanguages);
            setCurrentLanguage(mockLanguages[0]);
        } catch (error) {
            console.error('Error loading top bar data:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            window.location.href = `/search?q=${encodeURIComponent(searchQuery)}`;
        }
    };

    return (
        <div className="border-border bg-background/95 supports-[backdrop-filter]:bg-background/60 border-b backdrop-blur">
            {/* Top Info Bar */}
            <div className="bg-primary/5 border-border border-b">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-8 items-center justify-between text-xs">
                        <div className="text-muted-foreground flex items-center space-x-6">
                            <div className="flex items-center space-x-1">
                                <Phone className="h-3 w-3" />
                                <span>+1 (555) 123-4567</span>
                            </div>
                            <div className="flex items-center space-x-1">
                                <Mail className="h-3 w-3" />
                                <span>info@orions360.com</span>
                            </div>
                        </div>

                        <div className="flex items-center space-x-4">
                            <span>Free shipping on orders over $50</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main Top Bar */}
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="flex h-16 items-center justify-between gap-4">
                    {/* Logo - Fixed width and positioning */}
                    <div className="flex-shrink-0">
                        <Link
                            href={route('home')}
                            className="text-foreground relative text-3xl font-semibold transition-opacity hover:opacity-80 whitespace-nowrap"
                        >
                            <span className="text-primary">DS</span>
                            <span className="text-green-500 dark:text-green-700">stack</span>
                            <span className="text-primary text-4xl">.</span>
                            <Badge className="text-primary-foreground absolute -right-8 -top-1 bg-green-500 px-2 py-0.5 text-xs font-semibold">
                                plus
                            </Badge>
                        </Link>
                    </div>

                    {/* Address Selection - Fixed z-index and positioning */}
                    <div className="hidden items-center lg:flex relative z-100">
                        <NavigationMenu>
                            <NavigationMenuList>
                                <NavigationMenuItem>
                                    <NavigationMenuTrigger className="data-[state=open]:bg-accent data-[state=open]:text-accent-foreground h-9 max-w-[180px]">
                                        <MapPin className="mr-2 h-4 w-4 flex-shrink-0" />
                                        <span className="truncate">
                                            {loading ? (
                                                <Skeleton className="bg-muted h-4 w-20" />
                                            ) : (
                                                currentAddress?.city || 'Select Location'
                                            )}
                                        </span>
                                        {/*<ChevronDown className="ml-1 h-4 w-4 flex-shrink-0" />*/}
                                    </NavigationMenuTrigger>
                                    <NavigationMenuContent className="z-50">
                                        <div className="w-80 p-4">
                                            <h3 className="text-foreground mb-3 font-semibold">Select Delivery Location</h3>
                                            <div className="max-h-60 space-y-2 overflow-y-auto">
                                                {addresses.map((address) => (
                                                    <button
                                                        key={address.id}
                                                        className={`w-full rounded-lg border p-3 text-left transition-colors ${
                                                            currentAddress?.id === address.id
                                                                ? 'border-primary bg-primary/5'
                                                                : 'border-border hover:bg-accent'
                                                        }`}
                                                        onClick={() => setCurrentAddress(address)}
                                                    >
                                                        <div className="flex items-start justify-between">
                                                            <div className="flex-1">
                                                                <div className="mb-1 flex items-center space-x-2">
                                                                    <span className="text-foreground font-medium">{address.name}</span>
                                                                    {address.is_default && (
                                                                        <Badge variant="secondary" className="text-xs">
                                                                            Default
                                                                        </Badge>
                                                                    )}
                                                                </div>
                                                                <p className="text-muted-foreground text-sm">
                                                                    {address.street}, {address.city}, {address.state} {address.zip_code}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </button>
                                                ))}
                                            </div>
                                            <Button variant="outline" className="mt-3 w-full" asChild>
                                                <Link href="/addresses">Manage Addresses</Link>
                                            </Button>
                                        </div>
                                    </NavigationMenuContent>
                                </NavigationMenuItem>
                            </NavigationMenuList>
                        </NavigationMenu>
                    </div>

                    {/* Search Bar - Flexible width */}
                    <div className="flex-1 min-w-0 max-w-2xl mx-4 lg:mx-6">
                        <form onSubmit={handleSearch} className="relative">
                            <Search className="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 transform" />
                            <Input
                                type="text"
                                placeholder="Search for products, brands, and more..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="bg-background border-border focus:border-primary h-9 w-full pl-10 pr-10"
                            />
                            {searchQuery && (
                                <button
                                    type="button"
                                    onClick={() => setSearchQuery('')}
                                    className="text-muted-foreground hover:text-foreground absolute right-3 top-1/2 -translate-y-1/2 transform"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            )}
                        </form>
                    </div>

                    {/* Right Side Actions */}
                    <div className="flex items-center space-x-1 flex-shrink-0">
                        {/* Theme Toggle */}
                        <ThemeToggle />

                        {/* Language Selector */}
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm" className="h-9 gap-1">
                                    <Globe className="h-4 w-4" />
                                    <span className="hidden sm:inline">{currentLanguage?.code.toUpperCase()}</span>
                                    <ChevronDown className="h-3 w-3" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-48 z-50">
                                {languages.map((language) => (
                                    <DropdownMenuItem
                                        key={language.code}
                                        onClick={() => setCurrentLanguage(language)}
                                        className="flex items-center space-x-2"
                                    >
                                        <span className="text-lg">{language.flag}</span>
                                        <span>{language.name}</span>
                                        <span className="text-muted-foreground text-sm">({language.native_name})</span>
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>

                        {/* Login/Account */}
                        {auth.user ? (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="sm" className="h-9 gap-1">
                                        <User className="h-4 w-4" />
                                        <span className="hidden sm:inline">Account</span>
                                        <ChevronDown className="h-3 w-3" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-48 z-50">
                                    <DropdownMenuItem asChild>
                                        <Link href="/profile">My Profile</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <Link href="/orders">My Orders</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <Link href="/wishlist">My Wishlist</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <Link href="/logout" method="post" as="button">
                                            Logout
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        ) : (
                            <Button variant="ghost" size="sm" className="h-9 gap-1" asChild>
                                <Link href={route('login')}>
                                    <User className="h-4 w-4" />
                                    <span className="hidden sm:inline">Login</span>
                                </Link>
                            </Button>
                        )}

                        {/* Wishlist */}
                        <HeartIcon />

                        {/* Shopping Cart */}
                        <CartIcon />
                    </div>
                </div>
            </div>
        </div>
    );
}
