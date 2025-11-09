import PublicLayout from '@/pages/e-commerce/public/layout';
import ProductCard from '@/components/e-commerce/public/productCard';


const ProductList = ({ custom_fields, stores, products, pagination }) => {
    // Remove the duplicate products declaration and use the prop directly
    // const products = useSelector(state => state.product.list) // Remove this line

    return (
        <PublicLayout>
            <section className='bg-muted py-8 sm:py-16 lg:py-24'>
                <div className='mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8'>
                    <div className='space-y-4'>
                        <p className='text-sm font-medium'>Samsung watch</p>
                        <h2 className='text-2xl font-semibold sm:text-3xl lg:text-4xl'>
                            All New Collection
                        </h2>
                        {pagination && (
                            <p className='text-gray-600'>
                                Showing {((pagination.current_page - 1) * pagination.per_page) + 1}-
                                {Math.min(pagination.current_page * pagination.per_page, pagination.total)} of {pagination.total} products
                            </p>
                        )}
                    </div>

                    {products && products.length > 0 ? (
                        <>
                            <div className='grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3'>
                                {/* Product Cards */}
                                {products.map((product) => (
                                    <ProductCard key={product.id} product={product} />
                                ))}
                            </div>

                            {/* Pagination */}
                            {pagination && pagination.links && pagination.links.length > 3 && (
                                <div className='flex justify-center mt-8'>
                                    <nav className='flex space-x-2'>
                                        {/* Previous Button */}
                                        {pagination.links[0].url && (
                                            <a
                                                href={pagination.links[0].url}
                                                className='px-4 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50'
                                            >
                                                {pagination.links[0].label}
                                            </a>
                                        )}

                                        {/* Page Numbers */}
                                        {pagination.links.slice(1, -1).map((link, index) => (
                                            <a
                                                key={index}
                                                href={link.url}
                                                className={`px-4 py-2 text-sm border rounded ${
                                                    link.active
                                                        ? 'bg-blue-600 text-white border-blue-600'
                                                        : 'border-gray-300 hover:bg-gray-50'
                                                }`}
                                            >
                                                {link.label}
                                            </a>
                                        ))}

                                        {/* Next Button */}
                                        {pagination.links[pagination.links.length - 1].url && (
                                            <a
                                                href={pagination.links[pagination.links.length - 1].url}
                                                className='px-4 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50'
                                            >
                                                {pagination.links[pagination.links.length - 1].label}
                                            </a>
                                        )}
                                    </nav>
                                </div>
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
