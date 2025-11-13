"use client"

import React, { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Slider } from "@/components/ui/slider"
import { Badge } from "@/components/ui/badge"
import { Heart, Star, SlidersHorizontal, ShoppingCartIcon } from 'lucide-react';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from "@/components/ui/sheet"
import PublicLayout from '@/pages/e-commerce/public/layout';
import { Product, Store } from '@/types/eCommerce/ecom.product';
import { PaginationData } from '@/types/eCommerce/pagination';
import PaginationBottom from '@/components/e-commerce/public/paginationBottom';
import { useCart } from '@/contexts/CartContext';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

const categories = ["All", "Accessories", "Electronics", "Sports"]

const features = [
    "Water Resistant",
    "Heart Rate Monitor",
    "GPS",
    "Sleep Tracking",
    "Notifications",
    "Leather Strap",
    "Steel Band",
]

interface ShopPageProps {
    products: Product[];
    pagination: PaginationData;
    custom_fields: any[];
    stores: Store[];
}

const products = [
    {
        id: 1,
        name: "Classic Leather Watch",
        category: "Accessories",
        rating: 4.9,
        reviews: 128,
        price: 299,
        originalPrice: 399,
        image: "/white-elegant-leather-watch.jpg",
        tags: ["Water Resistant", "Leather Strap"],
    },
    {
        id: 2,
        name: "Smart Watch Pro",
        category: "Electronics",
        rating: 4.8,
        reviews: 256,
        price: 499,
        originalPrice: 599,
        image: "/white-smartwatch-technology.jpg",
        tags: ["Heart Rate Monitor", "GPS", "Sleep Tracking", "Notifications"],
    },
    {
        id: 3,
        name: "Sport Watch Elite",
        category: "Sports",
        rating: 4.7,
        reviews: 192,
        price: 399,
        originalPrice: 449,
        image: "/white-sports-watch.jpg",
        tags: ["Water Resistant", "Heart Rate Monitor", "GPS"],
    },
]

const  ShopPage:React.FC<ShopPageProps> = ({ products, pagination, custom_fields, stores }) => {
    const [selectedCategory, setSelectedCategory] = useState("All")
    const [priceRange, setPriceRange] = useState([0, 1000])
    const [minRating, setMinRating] = useState([0])
    const [selectedFeatures, setSelectedFeatures] = useState<string[]>([])
    const [searchQuery, setSearchQuery] = useState("")

    const toggleFeature = (feature: string) => {
        setSelectedFeatures((prev) => (prev.includes(feature) ? prev.filter((f) => f !== feature) : [...prev, feature]))
    }

    // Calculate showing range
    const getShowingRange = () => {
        if (!pagination) return { start: 0, end: 0 };

        const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
        const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
        return { start, end };
    };

    const showingRange = getShowingRange();

    const FiltersContent = () => (
        <div className="p-4 space-y-8">
            {/* Search */}
            <div>
                <h3 className="mb-4 text-sm font-semibold text-foreground">Search</h3>
                <Input
                    placeholder="Search products..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="bg-background"
                />
            </div>

            {/* Category */}
            <div>
                <h3 className="mb-4 text-sm font-semibold text-foreground">Category</h3>
                <div className="grid grid-cols-2 gap-2">
                    {categories.map((category) => (
                        <Button
                            key={category}
                            variant={selectedCategory === category ? "default" : "outline"}
                            size="sm"
                            onClick={() => setSelectedCategory(category)}
                            className="justify-center"
                        >
                            {category}
                        </Button>
                    ))}
                </div>
            </div>

            {/* Price Range */}
            <div>
                <h3 className="mb-4 text-sm font-semibold text-foreground">Price Range</h3>
                <Slider value={priceRange} onValueChange={setPriceRange} max={1000} step={10} className="mb-3" />
                <div className="flex justify-between text-sm text-muted-foreground">
                    <span>${priceRange[0]}</span>
                    <span>${priceRange[1]}</span>
                </div>
            </div>

            {/* Minimum Rating */}
            <div>
                <h3 className="mb-4 text-sm font-semibold text-foreground">Minimum Rating</h3>
                <Slider value={minRating} onValueChange={setMinRating} max={5} step={0.1} className="mb-3" />
                <div className="flex items-center gap-1 text-muted-foreground">
                    <Star className="h-4 w-4 fill-current" />
                    <span className="text-sm">{minRating[0].toFixed(1)}</span>
                </div>
            </div>

            {/* Features */}
            <div>
                <h3 className="mb-4 text-sm font-semibold text-foreground">Features</h3>
                <div className="space-y-2">
                    {features.map((feature) => (
                        <Button
                            key={feature}
                            variant={selectedFeatures.includes(feature) ? "default" : "outline"}
                            size="sm"
                            onClick={() => toggleFeature(feature)}
                            className="w-full justify-center"
                        >
                            {feature}
                        </Button>
                    ))}
                </div>
            </div>
        </div>
    )

    return (
        <PublicLayout>
            <section className='py-8 sm:py-16 lg:py-24'>
                <div className='mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8'>
            <div className="flex">
                <aside className="hidden w-[280px] rounded-md bg-card p-6 lg:block">
                    <FiltersContent />
                </aside>

                {/* Main Content */}
                <main className="flex-1 p-2 md:p-3 mt-3">
                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-base text-muted-foreground md:text-lg">{products.length} products found</h1>

                        <Sheet>
                            <SheetTrigger asChild>
                                <Button variant="outline" size="sm" className="lg:hidden bg-transparent">
                                    <SlidersHorizontal className="mr-2 h-4 w-4" />
                                    Filters
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="right" className="w-[300px] overflow-y-auto">
                                <SheetHeader className="-mb-4">
                                    <SheetTitle>Filters</SheetTitle>
                                </SheetHeader>
                                <FiltersContent />
                            </SheetContent>
                        </Sheet>
                    </div>

                        {products && products.length > 0 ? (
                            <>
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                    {/* Product Cards */}
                                    {products.map((product) => (
                                        <ProductCard key={product.id} product={product} />
                                    ))}
                                </div>

                                {/* Pagination */}
                                {pagination && pagination.links && pagination.links.length > 3 && (
                                    <PaginationBottom pagination={pagination} />
                                )}
                            </>
                        ) : ( <></>)
                        }
                </main>
            </div>
        </div>
            </section>
        </PublicLayout>
    )
}

const ProductCard = ({ product }) => {
    const { addToCart, cart } = useCart();
    const [loading, setLoading] = useState(false);
    const [addedToCart, setAddedToCart] = useState(false);

    const { id, name, slug, code, currency, price, cost, description, stocks, supplier, category, on_sale, active, photo } = product;
    // Calculate stock quantity
    const stockQuantity = stocks?.[0]?.balance || 0;

    // Check if product is in stock
    const inStock = stockQuantity > 0;

    const handleAddToCart = async () => {
        if (!inStock) return;

        setLoading(true);
        try {
            const success = await addToCart({
                id,
                name,
                qty: 1,
                price,
                options: { // Pass as object, not string because backend expect a options array
                    category: category?.name,
                    supplier: supplier?.name,
                    // Add any other options as key-value pairs
                }
            });

            if (success) {
                setAddedToCart(true);
                setTimeout(() => setAddedToCart(false), 2000);
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
        } finally {
            setLoading(false);
        }
    };

    // Check if product is on sale (you might want to add logic based on cost vs price)
    const isOnSale = cost && parseFloat(cost) > parseFloat(price);

    // Check if this product is already in cart
    const isInCart = cart.content.some((item) => item.id === id);

    return (
        <Card key={id} className="group overflow-hidden bg-card flex flex-col dark:bg-input/30">
            <Link href={`/product/${slug}`} className="group block max-xl:mx-auto">
                <div className="cursor-pointer">
                    <div className="relative aspect-square overflow-hidden bg-muted">
                        {photo ? (
                            <img src={photo} alt={name} className="h-full w-full rounded-lg object-cover" />
                        ) : (
                            <img src={"/placeholder.svg"} alt={'0360_product_placeholder'} className="h-full w-full rounded-lg object-cover" />
                        )}
                        <button className="absolute right-3 top-3 rounded-full bg-background p-2 shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100 hover:bg-accent">
                            <Heart className="h-5 w-5 text-foreground" />
                        </button>
                    </div>
                </div>
            </Link>

            <CardContent className="flex flex-1 flex-col pl-6">
                <div className="flex items-center justify-between pb-4">
                    {isOnSale ? (
                        <div className="flex items-center gap-2.5">
                                <span className="text-2xl font-semibold">
                                    {currency}
                                    {parseFloat(price).toFixed(2)}
                                </span>
                            <span className="text-muted-foreground font-medium line-through">
                                    {currency}
                                {parseFloat(cost || '0').toFixed(2)}
                                </span>
                        </div>
                    ) : (
                        <span className="text-2xl font-semibold">
                                {currency}
                            {parseFloat(price).toFixed(2)}
                            </span>
                    )}

                    <div className="flex items-center gap-1 text-sm text-gray-500">
                        <div className={`h-2 w-2 rounded-full ${inStock ? 'bg-green-500' : 'bg-red-500'}`}></div>
                        <span>{inStock ? `In Stock (${stockQuantity})` : 'Out of Stock'}</span>
                    </div>
                </div>
                <div className="flex-1 space-y-3">
                    <div className="space-y-2">
                        <Link href={`/product/${slug}`} className="group block max-xl:mx-auto">
                            <h3 className="line-clamp-2 text-lg font-semibold text-foreground">{name}</h3>
                        </Link>
                        <Badge variant="secondary" className="w-fit bg-muted text-foreground">
                            {category.name || "Uncategorized"}
                        </Badge>
                    </div>

                    {/*<div className="flex items-center gap-1">*/}
                    {/*    <Star className="h-4 w-4 fill-current text-foreground" />*/}
                    {/*    <span className="font-semibold text-foreground">{product.rating}</span>*/}
                    {/*    <span className="text-sm text-muted-foreground">({product.reviews})</span>*/}
                    {/*</div>*/}

                    {/*<div className="flex items-baseline gap-2">*/}
                    {/*    <span className="text-2xl font-bold text-foreground">${product.price}</span>*/}
                    {/*    <span className="text-sm text-muted-foreground line-through">${product.originalPrice}</span>*/}
                    {/*</div>*/}

                    {/*<div className="flex flex-wrap gap-2">*/}
                    {/*    {product.tags.map((tag) => (*/}
                    {/*        <Badge key={tag} variant="secondary" className="bg-background text-foreground">*/}
                    {/*            {tag}*/}
                    {/*        </Badge>*/}
                    {/*    ))}*/}
                    {/*</div>*/}
                </div>

                <Button
                    variant={addedToCart || isInCart ? 'secondary' : 'secondary'}
                    className={cn(
                        'mt-4 w-full transition-all duration-200 cursor-pointer',
                        (addedToCart || isInCart) && 'bg-green-500 text-white hover:bg-green-600',
                    )}
                    disabled={!inStock || loading}
                    onClick={handleAddToCart}
                    title={inStock ? 'Add to cart' : 'Out of stock'}
                >
                    {loading ? (
                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                    ) : addedToCart || isInCart ? (
                        <div className="flex items-center justify-center">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    ) : (
                        <>
                            <ShoppingCartIcon className="h-4 w-4" /> Add to Cart
                        </>
                    )}
                </Button>

            </CardContent>
        </Card>
    );
}

export default ShopPage;
