'use client'
import { Search, ShoppingCart, Moon, Sun } from "lucide-react";
import { router } from '@inertiajs/react';
import { useState } from "react";
import { useSelector } from "react-redux";
import { Link } from '@inertiajs/react';
import { route } from 'ziggy-js';

// Shadcn/ui components
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    NavigationMenu,
    NavigationMenuList,
    NavigationMenuItem,
    NavigationMenuLink,
} from "@/components/ui/navigation-menu";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Badge } from "@/components/ui/badge";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { ScrollArea } from "@/components/ui/scroll-area";
import { useAppearance } from '@/hooks/use-appearance';
import CartIcon from '@/components/e-commerce/public/cartIcon';

const Navbar = () => {
    const [search, setSearch] = useState('')
    const cartCount = useSelector(state => state.cart.total)
    const { appearance, updateAppearance } = useAppearance();

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        router.visit(`/shops?search=${search}`);
    }

    const navigationItems = [
        { href: route('home'), label: 'Home' },
        { href: route('shops'), label: 'Shop' },
        { href: route('about-us'), label: 'About' },
        { href: route('contact-us'), label: 'Contact' },
    ]

    return (
        <nav className="sticky relative bg-background border-b">
            <div className="mx-6">
                <div className="flex items-center justify-between max-w-7xl mx-auto py-4 transition-all">
                    {/* Logo */}
                    <Link
                        href={route('home')}
                        className="relative text-4xl font-semibold text-foreground hover:opacity-80 transition-opacity"
                    >
                        <span className="text-primary">DS</span><span className={`text-green-500 dark:text-green-700`}>stack</span>
                        <span className="text-primary text-5xl leading-0">.</span>
                        <Badge className="absolute text-xs font-semibold -top-1 -right-8 px-2 py-0.5 bg-green-500 text-primary-foreground">
                            plus
                        </Badge>
                    </Link>

                    {/* Desktop Navigation */}
                    <div className="hidden sm:flex items-center gap-4 lg:gap-8">
                        <NavigationMenu>
                            <NavigationMenuList className="gap-6">
                                {navigationItems.map((item) => (
                                    <NavigationMenuItem key={item.href}>
                                        <NavigationMenuLink asChild>
                                            <Link
                                                href={item.href}
                                                className="text-sm font-medium text-foreground/60 hover:text-foreground transition-colors"
                                            >
                                                {item.label}
                                            </Link>
                                        </NavigationMenuLink>
                                    </NavigationMenuItem>
                                ))}
                            </NavigationMenuList>
                        </NavigationMenu>

                        {/* Search Form */}
                        <form onSubmit={handleSearch} className="hidden xl:flex items-center w-xs text-sm gap-2">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    className="pl-10 pr-4 py-2 w-64 bg-muted border-0"
                                    type="text"
                                    placeholder="Search products"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    required
                                />
                            </div>
                        </form>

                        {/* Cart & Theme Toggle */}
                        <div className="flex items-center gap-4">
                            <CartIcon />

                            {/* Theme Toggle */}
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="icon">
                                        <Sun className="h-5 w-5 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
                                        <Moon className="absolute h-5 w-5 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
                                        <span className="sr-only">Toggle theme</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem onClick={() => updateAppearance("light")}>
                                        Light
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onClick={() => updateAppearance("dark")}>
                                        Dark
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onClick={() => updateAppearance("system")}>
                                        System
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <Button asChild>
                                <Link href={route('login')}>
                                    Login
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Mobile Navigation */}
                    <div className="sm:hidden flex items-center gap-2">
                        {/* Theme Toggle Mobile */}
                        <Button variant="ghost" size="icon" onClick={() => setTheme(theme === "dark" ? "light" : "dark")}>
                            <Sun className="h-5 w-5 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
                            <Moon className="absolute h-5 w-5 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
                            <span className="sr-only">Toggle theme</span>
                        </Button>

                        {/* Mobile Sheet Menu */}
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon">
                                    <svg
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <line x1="4" x2="20" y1="12" y2="12" />
                                        <line x1="4" x2="20" y1="6" y2="6" />
                                        <line x1="4" x2="20" y1="18" y2="18" />
                                    </svg>
                                </Button>
                            </SheetTrigger>
                            <SheetContent>
                                <ScrollArea className="h-full py-6">
                                    <div className="flex flex-col space-y-6">
                                        {/* Mobile Navigation Items */}
                                        {navigationItems.map((item) => (
                                            <Link
                                                key={item.href}
                                                href={item.href}
                                                className="text-lg font-medium text-foreground/60 hover:text-foreground transition-colors"
                                            >
                                                {item.label}
                                            </Link>
                                        ))}

                                        {/* Mobile Search */}
                                        <form onSubmit={handleSearch} className="space-y-4">
                                            <div className="relative">
                                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                                <Input
                                                    className="pl-10 pr-4 py-2 bg-muted border-0"
                                                    type="text"
                                                    placeholder="Search products"
                                                    value={search}
                                                    onChange={(e) => setSearch(e.target.value)}
                                                    required
                                                />
                                            </div>
                                        </form>

                                        {/* Mobile Cart */}
                                        <Button variant="ghost" className="justify-start" asChild>
                                            <Link href={route('cart')} className="relative">
                                                <ShoppingCart className="h-5 w-5 mr-2" />
                                                Cart
                                                {cartCount > 0 && (
                                                    <Badge
                                                        variant="secondary"
                                                        className="ml-2 h-5 w-5 min-w-0 p-0 flex items-center justify-center text-xs"
                                                    >
                                                        {cartCount}
                                                    </Badge>
                                                )}
                                            </Link>
                                        </Button>

                                        {/* Mobile Login */}
                                        <Button asChild className="w-full">
                                            <Link href={route('login')}>
                                                Login
                                            </Link>
                                        </Button>
                                    </div>
                                </ScrollArea>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>
            </div>
        </nav>
    )
}

export default Navbar
