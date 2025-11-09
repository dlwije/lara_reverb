'use client'
import Title from '@/components/e-commerce/public/title';
import { useSelector } from 'react-redux'
import ProductCard from '@/components/e-commerce/public/productCard';

const BestSelling = () => {

    const displayQuantity = 8
    const products = useSelector(state => state.product.list)

    return (
        <section className='px-6 my-30 max-w-6xl mx-auto'>
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8">
                <Title title='Best Selling'
                       description={`Showing ${products.length < displayQuantity ? products.length : displayQuantity} of ${products.length} products`}
                       href='/products' />
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {products.slice().sort((a, b) => b.rating.length - a.rating.length).slice(0, displayQuantity).map((product, index) => (
                        <ProductCard key={index} product={product} />
                    ))}
                </div>
            </div>
        </section>
    )
}

export default BestSelling;
