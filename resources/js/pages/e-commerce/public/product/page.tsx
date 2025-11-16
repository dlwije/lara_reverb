'use client'
import { usePage } from '@inertiajs/react';
import { useEffect } from "react";
import PublicLayout from '@/pages/e-commerce/public/layout';
import ProductDetail from '@/components/e-commerce/public/product/product-detail';

export default function Product() {
    const { single_product } = usePage().props; // Get the product directly from Laravel

    useEffect(() => {
        scrollTo(0, 0)
    }, []);

    return (
        <PublicLayout>
            <div className="mx-6">
                <div className="max-w-7xl mx-auto">
                    {/* Breadcrumbs */}
                    <div className="text-gray-600 text-sm mt-8 mb-5">
                        Home / Products / {single_product?.category?.name}
                    </div>

                    <ProductDetail single_product={single_product} />
                </div>
            </div>
        </PublicLayout>
    );
}
