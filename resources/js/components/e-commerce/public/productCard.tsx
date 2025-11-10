'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useCart } from '@/contexts/CartContext';
import { cn } from '@/lib/utils';
import { Product } from '@/types/eCommerce/ecom.product';
import { Link } from '@inertiajs/react';
import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import { HeartIcon, ShoppingCartIcon } from 'lucide-react';
import React, { useState } from 'react';

interface ProductCardProps {
    product: Product;
    coming_from?: string;
}
const ProductCard: React.FC<ProductCardProps> = ({ product, coming_from }) => {
    const currency = import.meta.env.NEXT_PUBLIC_CURRENCY_SYMBOL || 'AED'; // Changed default to ₹ based on your price data
    const { addToCart, cart } = useCart();
    const [loading, setLoading] = useState(false);
    const [addedToCart, setAddedToCart] = useState(false);

    const productData = product;
    // console.log('productData', productData);
    // Use actual data from your API response
    const { id, name, slug, code, price, cost, description, stocks, supplier, category, on_sale, active, photo } = productData;

    // Calculate stock quantity
    const stockQuantity = stocks?.[0]?.balance || 0;

    // Check if product is in stock
    const inStock = stockQuantity > 0;

    // Check if product is on sale (you might want to add logic based on cost vs price)
    const isOnSale = cost && parseFloat(cost) > parseFloat(price);

    // Check if this product is already in cart
    const isInCart = cart.content.some((item) => item.id === id);

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

    return (
        <Card className={cn(`${coming_from == 'best-selling' ? 'border-none hover:shadow-md' : 'border-none shadow-none'}`, isOnSale && 'relative')}>
            {/* Sale Badge */}
            {/*{isOnSale && (*/}
            {/*    <div className="absolute left-2 top-2 z-10">*/}
            {/*        <Badge className="bg-red-500 text-white">Sale</Badge>*/}
            {/*    </div>*/}
            {/*)}*/}

            {/*/!* In Cart Badge *!/*/}
            {/*{isInCart && (*/}
            {/*    <div className="absolute right-2 top-2 z-10">*/}
            {/*        <Badge className="bg-green-500 text-white">In Cart</Badge>*/}
            {/*    </div>*/}
            {/*)}*/}

            {/*/!* Out of Stock Overlay *!/*/}
            {/*{!inStock && (*/}
            {/*    <div className="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-black bg-opacity-50">*/}
            {/*        <Badge className="bg-gray-600 text-sm text-white">Out of Stock</Badge>*/}
            {/*    </div>*/}
            {/*)}*/}

            <CardContent className="flex flex-1 flex-col justify-between gap-6 pt-6">
                <Link href={`/product/${slug}`} className="group block max-xl:mx-auto">
                    <div className="cursor-pointer">
                        <div className="size-80 relative mx-auto flex items-center justify-center rounded-lg bg-gray-200">
                            {photo ? (
                                <img src={photo} alt={name} className="h-full w-full rounded-lg object-cover" />
                            ) : (
                                <span className="text-sm text-gray-400">No Image</span>
                            )}
                        </div>
                    </div>
                </Link>

                <div className="space-y-4">
                    <div className="flex flex-col gap-2 text-center">
                        <Link href={`/product/${slug}`} className="group block max-xl:mx-auto">
                            <div className="cursor-pointer">
                                <h3 className="line-clamp-2 text-xl font-semibold">{name}</h3>
                                <p className="mt-1 text-sm text-gray-500">{code}</p>
                            </div>
                        </Link>

                        {category && (
                            <div className="flex items-center justify-center gap-2">
                                <Badge className="bg-blue-600/10 text-blue-600 focus-visible:outline-none focus-visible:ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-400 dark:focus-visible:ring-blue-400/40">
                                    {category.name}
                                </Badge>
                            </div>
                        )}
                    </div>

                    <Separator />

                    {description && <p className="line-clamp-2 text-center text-sm text-gray-600">{description}</p>}

                    <div className="flex items-center justify-between">
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

                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-1">
                            <CheckboxPrimitive.Root
                                data-slot="checkbox"
                                className="focus-visible:ring-ring/50 focus-visible:ring-3 group rounded-sm p-2.5 outline-none"
                                aria-label="Heart icon"
                            >
                                <span className="group-data-[state=checked]:hidden">
                                    <HeartIcon className="size-4" />
                                </span>
                                <span className="group-data-[state=unchecked]:hidden">
                                    <HeartIcon className="fill-destructive stroke-destructive size-4" />
                                </span>
                            </CheckboxPrimitive.Root>
                        </div>

                        <Button
                            variant={addedToCart || isInCart ? 'default' : 'ghost'}
                            className={cn(
                                'size-9 transition-all duration-200 cursor-pointer',
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
                                <ShoppingCartIcon className="h-4 w-4" />
                            )}
                        </Button>
                    </div>

                    <Button
                        variant={addedToCart || isInCart ? 'default' : 'outline'}
                        className={cn(
                            'w-full transition-all duration-200 cursor-pointer',
                            (addedToCart || isInCart) && 'bg-green-500 text-white hover:bg-green-600',
                        )}
                        disabled={!inStock || loading}
                        onClick={handleAddToCart}
                        size="sm"
                    >
                        {loading ? (
                            <>
                                <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                Adding...
                            </>
                        ) : addedToCart || isInCart ? (
                            <>
                                <svg className="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                                {isInCart ? 'In Cart' : 'Added!'}
                            </>
                        ) : (
                            <>
                                <ShoppingCartIcon className="mr-2 h-4 w-4" />
                                Add to Cart
                            </>
                        )}
                    </Button>

                    {supplier && <div className="text-center text-xs text-gray-500">Supplier: {supplier.name}</div>}
                </div>
            </CardContent>
        </Card>
    );
};

export default ProductCard;
