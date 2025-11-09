'use client';
import React from 'react';
import Title from '@/components/e-commerce/public/title';
import { useSelector } from 'react-redux';
import ProductCard from '@/components/e-commerce/public/productCard';

const LatestProducts = () => {

    const displayQuantity = 4;
    const products = useSelector(state => state.product.list);

    return (
        <section className="bg-muted py-8 sm:py-16 lg:py-24">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8">
                <Title title="Latest Products"
                       description={`Showing ${products.length < displayQuantity ? products.length : displayQuantity} of ${products.length} products`}
                       href="/shops" />
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {products.slice().sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt)).slice(0, displayQuantity).map((product, index) => (
                        <ProductCard key={index} product={product} />
                    ))}
                </div>
            </div>
        </section>
    );
};

export default LatestProducts;
