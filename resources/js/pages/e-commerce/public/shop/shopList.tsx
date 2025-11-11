'use client'
import { Suspense } from "react"
import { MoveLeftIcon } from "lucide-react"
import { usePage } from '@inertiajs/react';
// import { useSelector } from "react-redux"
import { router } from '@inertiajs/react';
import PublicLayout from '@/pages/e-commerce/public/layout';
import ProductCard from '@/components/e-commerce/public/productCard';

function ShopContent() {

    // get query params ?search=abc
    const { props } = usePage();
    // const searchParams = useSearchParams()
    const search = props.filters?.search || ''; // if you pass filters via props

    // const products = useSelector(state => state.product.list)

    const filteredProducts = search
        ? products.filter(product =>
            product.name.toLowerCase().includes(search.toLowerCase())
        )
        : products;

    return (
        <PublicLayout>
            <div className="min-h-[70vh] mx-6">
                <div className=" max-w-7xl mx-auto">
                    <h1 onClick={() => router.get('/shop', { search })} className="text-2xl text-slate-500 my-6 flex items-center gap-2 cursor-pointer"> {search && <MoveLeftIcon size={20} />}  All <span className="text-slate-700 font-medium">Products</span></h1>
                    <div className="grid grid-cols-2 sm:flex flex-wrap gap-6 xl:gap-12 mx-auto mb-32">
                        {filteredProducts.map((product) => <ProductCard key={product.id} product={product} />)}
                    </div>
                </div>
            </div>
        </PublicLayout>
    )
}


export default function Shop() {
    return (
        <Suspense fallback={<div>Loading shop...</div>}>
            <ShopContent />
        </Suspense>
    );
}
