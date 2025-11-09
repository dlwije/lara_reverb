import { HeartIcon, ShoppingCartIcon } from 'lucide-react'

import * as CheckboxPrimitive from '@radix-ui/react-checkbox'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'

import { cn } from '@/lib/utils'
import ProductCard from '@/components/e-commerce/ProductCard';
import { useSelector } from 'react-redux';
import PublicLayout from '@/pages/e-commerce/(public)/layout.jsx';


const ProductList = () => {

    const displayQuantity = 8
    const products = useSelector(state => state.product.list)
    return (
        <PublicLayout>
        <section className='bg-muted py-8 sm:py-16 lg:py-24'>
            <div className='mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8'>
                <div className='space-y-4'>
                    <p className='text-sm font-medium'>Samsung watch</p>
                    <h2 className='text-2xl font-semibold sm:text-3xl lg:text-4xl'>All New Collection</h2>
                </div>

                <div className='grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3'>
                    {/* Product Cards */}
                    {products.map((product, index) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </div>
        </section>
        </PublicLayout>
    )
}

export default ProductList
