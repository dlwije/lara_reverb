'use client'
import ProductDescription from '@/components/e-commerce/ProductDescription.jsx';
import ProductDetails from '@/components/e-commerce/ProductDetails.jsx';
import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { useEffect, useState } from "react";
import { useSelector } from "react-redux";
import PublicLayout from '@/pages/e-commerce/(public)/layout.jsx';

export default function Product() {

    const { productId } = usePage().props; // get ID from Laravel
    const [product, setProduct] = useState();
    const products = useSelector(state => state.product.list);

    const fetchProduct = async () => {
        const product = products.find((product) => product.id === productId);
        setProduct(product);
    }

    useEffect(() => {
        if (products.length > 0) {
            fetchProduct()
        }
        scrollTo(0, 0)
    }, [productId,products]);

    return (
        <PublicLayout>
        <div className="mx-6">
            <div className="max-w-7xl mx-auto">

                {/* Breadcrums */}
                <div className="  text-gray-600 text-sm mt-8 mb-5">
                    Home / Products / {product?.category}
                </div>

                {/* Product Details */}
                {product && (<ProductDetails product={product} />)}

                {/* Description & Reviews */}
                {product && (<ProductDescription product={product} />)}
            </div>
        </div>
        </PublicLayout>
    );
}
