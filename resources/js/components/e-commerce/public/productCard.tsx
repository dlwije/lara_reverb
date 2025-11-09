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

    const currency = import.meta.NEXT_PUBLIC_CURRENCY_SYMBOL || '$'

    // calculate the average rating of the product
    const rating = Math.round(product.rating.reduce((acc, curr) => acc + curr.rating, 0) / product.rating.length);

    return (
        <Link href={`/product/${product.id}`} className='group max-xl:mx-auto block'>
            <Card className={cn('border-none shadow-none', product.price && 'relative')}>
                {/* Sale Badge */}
                {product.price && (
                    <Badge
                        className='bg-destructive/10 [a&]:hover:bg-destructive/5 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 text-destructive absolute top-6 left-6 px-3 py-1 uppercase focus-visible:outline-none'>
                        Sale
                    </Badge>
                )}

                <CardContent className='flex flex-1 flex-col justify-between gap-6'>
                    {/* Product Image */}
                    <div className="cursor-pointer">
                        <img src={product.images[0]} alt={product.name} className='mx-auto size-50' />
                    </div>

                    {/* Product Details */}
                    <div className='space-y-4'>
                        <div className='flex flex-col gap-2 text-center'>
                            <div className="cursor-pointer">
                                <h3 className='text-xl font-semibold'>{product.name}</h3>
                            </div>
                            {/*<div className='flex items-center justify-center gap-2'>*/}
                            {/*    {product.badges.map((badge, idx) => (*/}
                            {/*        <Badge*/}
                            {/*            key={idx}*/}
                            {/*            className='bg-green-600/10 text-green-600 focus-visible:ring-green-600/20 focus-visible:outline-none dark:bg-green-400/10 dark:text-green-400 dark:focus-visible:ring-green-400/40'*/}
                            {/*        >*/}
                            {/*            {badge}*/}
                            {/*        </Badge>*/}
                            {/*    ))}*/}
                            {/*</div>*/}
                        </div>

                        <Separator />

                        {/* Product Price */}
                        <div className='flex items-center justify-between'>
                            {!product.price &&
                                <span className='text-2xl font-semibold'>${product.price.toFixed(2)}</span>}
                            {product.price && (
                                <div className='flex items-center gap-2.5'>
                                    <span className='text-2xl font-semibold'>${product.price.toFixed(2)}</span>
                                    <span className='text-muted-foreground font-medium line-through'>
                                ${product.price.toFixed(2)}
                            </span>
                                </div>
                            )}

                            <div>
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

                                <Button variant='ghost' className='size-9'>
                                    <ShoppingCartIcon />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </Link>
    )
}

export default ProductCard
