import PublicLayout from '@/pages/e-commerce/public/layout';
import ProductCard from '@/components/e-commerce/public/productCard';
import { ProductListResponse } from '@/types/eCommerce/ecom.product';
import React from 'react';
import PaginationBottom from '@/components/e-commerce/public/paginationBottom';


const ProductList: React.FC<ProductListResponse> = ({ products, pagination, custom_fields, stores }) => {
    // Remove the duplicate products declaration and use the prop directly
    // const products = useSelector(state => state.product.list) // Remove this line

    // Calculate showing range
    const getShowingRange = () => {
        if (!pagination) return { start: 0, end: 0 };

        const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
        const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
        return { start, end };
    };

    const showingRange = getShowingRange();

    return (
        <PublicLayout>
            <section className='bg-muted py-8 sm:py-16 lg:py-24'>
                <div className='mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8'>
                    <div className='space-y-4'>
                        {/*<p className='text-sm font-medium'>Samsung watch</p>*/}
                        <h2 className='text-2xl font-semibold sm:text-3xl lg:text-4xl'>
                            All New Collection
                        </h2>
                        {pagination && (
                            <p className='text-gray-600'>
                                Showing {showingRange.start}-{showingRange.end} of {pagination.total} products
                            </p>
                        )}
                    </div>

                    {products && products.length > 0 ? (
                        <>
                            <div className='grid grid-cols-2 gap-6 md:grid-cols-2 lg:grid-cols-3'>
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
                    ) : (
                        <div className='text-center py-12'>
                            <p className='text-gray-500 text-lg'>No products found</p>
                        </div>
                    )}
                </div>
            </section>
        </PublicLayout>
    );
};

export default ProductList;
