'use client'
import { HeartIcon, ShoppingCartIcon, StarIcon } from 'lucide-react';
import { Link } from '@inertiajs/react';
import React from 'react'
// import InertiaImage from '@/components/e-commerce/InertiaImage.jsx';

import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import * as CheckboxPrimitive from '@radix-ui/react-checkbox'
import { cn } from '@/lib/utils'

const ProductCard = ({ product }) => {
    const currency = import.meta.env.NEXT_PUBLIC_CURRENCY_SYMBOL || '₹' // Changed default to ₹ based on your price data

    // Use actual data from your API response
    const {
        id,
        name,
        code,
        price,
        cost,
        description,
        stocks,
        supplier,
        category,
        on_sale,
        active
    } = product;

    // Calculate stock quantity
    const stockQuantity = stocks?.[0]?.balance || 0;

    // Check if product is in stock
    const inStock = stockQuantity > 0;

    // Check if product is on sale (you might want to add logic based on cost vs price)
    const isOnSale = cost && parseFloat(cost) > parseFloat(price);

    return (
        <Link href={`/product/${id}`} className='group max-xl:mx-auto block'>
            <Card className={cn('border-none shadow-none', isOnSale && 'relative')}>
                {/* Sale Badge */}
                {isOnSale && (
                    <Badge
                        className='bg-destructive/10 [a&]:hover:bg-destructive/5 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 text-destructive absolute top-6 left-6 px-3 py-1 uppercase focus-visible:outline-none'>
                        Sale
                    </Badge>
                )}

                {/* Out of Stock Badge */}
                {!inStock && (
                    <Badge
                        className='bg-gray-500/10 [a&]:hover:bg-gray-500/5 focus-visible:ring-gray-500/20 dark:focus-visible:ring-gray-500/40 text-gray-600 absolute top-6 left-6 px-3 py-1 uppercase focus-visible:outline-none'>
                        Out of Stock
                    </Badge>
                )}

                <CardContent className='flex flex-1 flex-col justify-between gap-6'>
                    {/* Product Image */}
                    <div className="cursor-pointer">
                        {/* Replace with actual image when available */}
                        <div className='mx-auto size-50 bg-gray-200 rounded-lg flex items-center justify-center'>
                            {product.photo ? (
                                <img
                                    src={product.photo}
                                    alt={name}
                                    className='w-full h-full object-cover rounded-lg'
                                />
                            ) : (
                                <span className="text-gray-400 text-sm">No Image</span>
                            )}
                        </div>
                    </div>

                    {/* Product Details */}
                    <div className='space-y-4'>
                        <div className='flex flex-col gap-2 text-center'>
                            <div className="cursor-pointer">
                                <h3 className='text-xl font-semibold'>{name}</h3>
                                <p className='text-sm text-gray-500 mt-1'>{code}</p>
                            </div>

                            {/* Category Badge */}
                            {category && (
                                <div className='flex items-center justify-center gap-2'>
                                    <Badge
                                        className='bg-blue-600/10 text-blue-600 focus-visible:ring-blue-600/20 focus-visible:outline-none dark:bg-blue-400/10 dark:text-blue-400 dark:focus-visible:ring-blue-400/40'
                                    >
                                        {category.name}
                                    </Badge>
                                </div>
                            )}
                        </div>

                        <Separator />

                        {/* Product Description */}
                        {description && (
                            <p className='text-sm text-gray-600 text-center line-clamp-2'>
                                {description}
                            </p>
                        )}

                        {/* Product Price */}
                        <div className='flex items-center justify-between'>
                            {isOnSale ? (
                                <div className='flex items-center gap-2.5'>
                                    <span className='text-2xl font-semibold'>
                                        {currency}{parseFloat(price).toFixed(2)}
                                    </span>
                                    <span className='text-muted-foreground font-medium line-through'>
                                        {currency}{parseFloat(cost).toFixed(2)}
                                    </span>
                                </div>
                            ) : (
                                <span className='text-2xl font-semibold'>
                                    {currency}{parseFloat(price).toFixed(2)}
                                </span>
                            )}

                            {/* Stock Indicator */}
                            <div className='flex items-center gap-1 text-sm text-gray-500'>
                                <div className={`w-2 h-2 rounded-full ${inStock ? 'bg-green-500' : 'bg-red-500'}`}></div>
                                <span>{inStock ? 'In Stock' : 'Out of Stock'}</span>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className='flex items-center justify-between'>
                            <div className='flex items-center gap-1'>
                                <CheckboxPrimitive.Root
                                    data-slot='checkbox'
                                    className='group focus-visible:ring-ring/50 rounded-sm p-2.5 outline-none focus-visible:ring-3'
                                    aria-label='Heart icon'
                                >
                                    <span className='group-data-[state=checked]:hidden'>
                                        <HeartIcon className='size-4' />
                                    </span>
                                    <span className='group-data-[state=unchecked]:hidden'>
                                        <HeartIcon className='fill-destructive stroke-destructive size-4' />
                                    </span>
                                </CheckboxPrimitive.Root>
                            </div>

                            <Button
                                variant='ghost'
                                className='size-9'
                                disabled={!inStock}
                            >
                                <ShoppingCartIcon />
                            </Button>
                        </div>

                        {/* Supplier Info */}
                        {supplier && (
                            <div className='text-xs text-gray-500 text-center'>
                                Supplier: {supplier.name}
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>
        </Link>
    )
}

export default ProductCard
