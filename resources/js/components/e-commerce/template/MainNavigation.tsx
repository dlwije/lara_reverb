// components/Layout/MainNavigation.tsx
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
} from '@/components/ui/navigation-menu';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { Menu, ChevronDown } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';

export function MainNavigation() {
    const { categories } = usePage().props;

    return (
        <>
            {/* Desktop Navigation */}
            <div className="hidden lg:block border-b border-border">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <NavigationMenu>
                        <NavigationMenuList>
                            {/* Categories Dropdown */}
                            <NavigationMenuItem>
                                <NavigationMenuTrigger className="h-12 data-[state=open]:bg-accent data-[state=open]:text-accent-foreground">
                                    Categories
                                    {/*<ChevronDown className="h-4 w-4 ml-1" />*/}
                                </NavigationMenuTrigger>
                                <NavigationMenuContent>
                                    <div className="w-[600px] p-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            {/* You would map through actual categories here */}
                                            <div className="space-y-2">
                                                <h3 className="font-semibold mb-2 text-foreground">Popular Categories</h3>
                                                <NavigationMenuLink asChild>
                                                    <Link
                                                        href="/categories/electronics"
                                                        className="block p-2 rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                                                    >
                                                        Electronics
                                                    </Link>
                                                </NavigationMenuLink>
                                                <NavigationMenuLink asChild>
                                                    <Link
                                                        href="/categories/clothing"
                                                        className="block p-2 rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                                                    >
                                                        Clothing
                                                    </Link>
                                                </NavigationMenuLink>
                                                <NavigationMenuLink asChild>
                                                    <Link
                                                        href="/categories/home-garden"
                                                        className="block p-2 rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                                                    >
                                                        Home & Garden
                                                    </Link>
                                                </NavigationMenuLink>
                                            </div>
                                            <div className="space-y-2">
                                                <h3 className="font-semibold mb-2 text-foreground">More Categories</h3>
                                                <NavigationMenuLink asChild>
                                                    <Link
                                                        href="/categories/sports"
                                                        className="block p-2 rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                                                    >
                                                        Sports
                                                    </Link>
                                                </NavigationMenuLink>
                                                <NavigationMenuLink asChild>
                                                    <Link
                                                        href="/categories/books"
                                                        className="block p-2 rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                                                    >
                                                        Books
                                                    </Link>
                                                </NavigationMenuLink>
                                                <NavigationMenuLink asChild>
                                                    <Link
                                                        href="/categories/beauty"
                                                        className="block p-2 rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                                                    >
                                                        Beauty
                                                    </Link>
                                                </NavigationMenuLink>
                                            </div>
                                        </div>
                                    </div>
                                </NavigationMenuContent>
                            </NavigationMenuItem>

                            {/* Other Navigation Items */}
                            <NavigationMenuItem>
                                <NavigationMenuLink asChild>
                                    <Link
                                        href={`/deals`}
                                        className="group inline-flex h-12 w-max items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground focus:outline-none"
                                    >
                                        Today's Deals
                                    </Link>
                                </NavigationMenuLink>
                            </NavigationMenuItem>

                            <NavigationMenuItem>
                                <NavigationMenuLink asChild>
                                    <Link
                                        href={`/product/new-arrivals`}
                                        className="group inline-flex h-12 w-max items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground focus:outline-none"
                                    >
                                        New Arrivals
                                    </Link>
                                </NavigationMenuLink>
                            </NavigationMenuItem>

                            <NavigationMenuItem>
                                <NavigationMenuLink asChild>
                                    <Link
                                        href="/brands"
                                        className="group inline-flex h-12 w-max items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground focus:outline-none"
                                    >
                                        Brands
                                    </Link>
                                </NavigationMenuLink>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>
            </div>

            {/* Mobile Navigation */}
            <div className="lg:hidden border-b border-border">
                <div className="px-4 py-2">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="sm" className="w-full justify-start gap-2">
                                <Menu className="h-4 w-4" />
                                Browse Categories
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="w-80 px-4">
                            <div className="space-y-6 py-6">
                                <h3 className="font-semibold text-lg">Categories</h3>
                                <div className="space-y-2">
                                    <Link
                                        href="/categories/electronics"
                                        className="block p-2 rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                                    >
                                        Electronics
                                    </Link>
                                    <Link
                                        href="/categories/clothing"
                                        className="block p-2 rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                                    >
                                        Clothing
                                    </Link>
                                    <Link
                                        href="/categories/home-garden"
                                        className="block p-2 rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                                    >
                                        Home & Garden
                                    </Link>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </>
    );
}
