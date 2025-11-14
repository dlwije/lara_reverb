import { usePage, Link, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { cn } from '@/lib/utils';
import ProductCard from '@/components/e-commerce/public/productCard';
import PublicLayout from '@/pages/e-commerce/public/layout';
import PaginationBottom from '@/components/e-commerce/public/paginationBottom';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator
} from '@/components/ui/breadcrumb';

export default function CategoryShow() {
    const {
        category,
        products,
        pagination,
        filters,
        breadcrumb,
        childCategories,
        parentCategory,
        stores
    } = usePage().props;

    // Fix: Handle null/undefined filters properly with safe initialization
    const safeFilters = filters && typeof filters === 'object' ? filters : {};
    const [localFilters, setLocalFilters] = useState(safeFilters);
    const [sortBy, setSortBy] = useState(safeFilters);
    const [searchTimeout, setSearchTimeout] = useState(null);
    const [filterLoading, setFilterLoading] = useState(false);
    const [filterError, setFilterError] = useState(null);

    // Cleanup timeout on unmount
    useEffect(() => {
        return () => {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
        };
    }, [searchTimeout]);
    //
    // // Update filters when they change from server
    useEffect(() => {
        const newSafeFilters = filters && typeof filters === 'object' ? filters : {};
        setLocalFilters(newSafeFilters);
        // setSortBy(newSafeFilters.sort || 'newest');
    }, [filters]);

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters };
        if (value) {
            newFilters[key] = value;
        } else {
            delete newFilters[key];
        }
        setLocalFilters(newFilters);

        // Debounce search to avoid too many requests
        if (key === 'search') {
            clearTimeout(searchTimeout);
            setSearchTimeout(setTimeout(() => updateFilters(newFilters), 500));
        } else {
            updateFilters(newFilters);
        }
    };

    const handleSortChange = (sort) => {
        setSortBy(sort);
        const newFilters = { ...localFilters, sort };
        updateFilters(newFilters);
    };

    const updateFilters = async (newFilters) => {
        try {
            setFilterLoading(true);
            setFilterError(null);
            console.log('Updating filters:', newFilters);

            await router.get(route('product.categories.show', category.slug), { filters: newFilters }, {
                preserveState: true,
                replace: true,
            });

        } catch (err) {
            setFilterError(err.message);
            console.error('Error updating filters:', err);
        } finally {
            setFilterLoading(false);
        }
    };

    // Clear filter error after 5 seconds
    useEffect(() => {
        if (filterError) {
            const timer = setTimeout(() => {
                setFilterError(null);
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [filterError]);

    if (filterLoading) {
        return (
            <div className="min-h-screen bg-background">
                {/* Breadcrumb Skeleton */}
                <div className="border-b">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <nav className="flex py-4">
                            <div className="flex items-center space-x-4">
                                <Skeleton className="h-4 w-16 rounded-full" />
                                <Skeleton className="h-4 w-4 rounded-full" />
                                <Skeleton className="h-4 w-24 rounded-full" />
                                <Skeleton className="h-4 w-4 rounded-full" />
                                <Skeleton className="h-4 w-32 rounded-full" />
                            </div>
                        </nav>
                    </div>
                </div>

                {/* Category Header Skeleton */}
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
                    <div className="text-center mb-8 space-y-4">
                        <Skeleton className="h-12 w-96 mx-auto rounded-xl" />
                        <Skeleton className="h-6 w-64 mx-auto rounded-lg" />
                    </div>

                    {/* Child Categories Skeleton */}
                    <div className="mb-12">
                        <Skeleton className="h-8 w-48 mx-auto mb-6 rounded-lg" />
                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                            {Array.from({ length: 6 }).map((_, index) => (
                                <div key={index} className="flex flex-col items-center gap-3">
                                    <Skeleton className="w-24 h-24 md:w-28 md:h-28 rounded-full" />
                                    <Skeleton className="h-4 w-20 rounded-full" />
                                    <Skeleton className="h-3 w-16 rounded-full" />
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Filters and Sorting Skeleton */}
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 p-6 bg-card rounded-lg border">
                        <Skeleton className="h-10 w-64 rounded-md" />
                        <div className="flex items-center gap-4">
                            <Skeleton className="h-4 w-16 rounded-full" />
                            <Skeleton className="h-10 w-32 rounded-md" />
                        </div>
                    </div>

                    {/* Products Grid Skeleton */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {Array.from({ length: 8 }).map((_, index) => (
                            <div key={index} className="bg-card rounded-lg border overflow-hidden">
                                <Skeleton className="h-48 w-full" />
                                <div className="p-4 space-y-3">
                                    <Skeleton className="h-5 w-3/4 rounded-lg" />
                                    <Skeleton className="h-4 w-1/2 rounded-lg" />
                                    <Skeleton className="h-6 w-1/3 rounded-lg" />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <PublicLayout>
            <section className="bg-background py-8 sm:py-8 lg:py-10">
                <div className="mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-6 lg:px-8">
                    {/* Breadcrumb */}
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink asChild>
                                    <Link href="/">Home</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            {breadcrumb?.map((item, index) => [
                                <BreadcrumbSeparator key={`separator-${item.slug}`} />,
                                <BreadcrumbItem key={item.slug}>
                                    {index === breadcrumb.length - 1 ? (
                                        <BreadcrumbPage>{item.name}</BreadcrumbPage>
                                    ) : (
                                        <BreadcrumbLink asChild>
                                            <Link href={item.url}>{item.name}</Link>
                                        </BreadcrumbLink>
                                    )}
                                </BreadcrumbItem>
                            ])}
                        </BreadcrumbList>
                    </Breadcrumb>

                    {/* Category Header */}
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
                        <div className="text-center mb-8">
                            <h1 className="text-3xl md:text-4xl font-bold text-foreground mb-4">
                                {category?.name || 'Category'}
                            </h1>
                            {category?.description && (
                                <p className="text-muted-foreground text-lg max-w-2xl mx-auto">
                                    {category.description}
                                </p>
                            )}
                        </div>

                        {/* Child Categories */}
                        {childCategories && childCategories.length > 0 && (
                            <div className="mb-12">
                                <h2 className="text-2xl font-semibold text-foreground mb-6 text-center">
                                    Subcategories
                                </h2>
                                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                                    {childCategories.map((childCategory) => (
                                        <Link
                                            key={childCategory.id}
                                            href={route('categories.show', childCategory.slug)}
                                            className="flex flex-col items-center gap-3 group cursor-pointer"
                                        >
                                            <div
                                                className="relative w-24 h-24 md:w-28 md:h-28 rounded-full overflow-hidden bg-muted">
                                                <img
                                                    src={childCategory.image || '/placeholder.svg'}
                                                    alt={childCategory.name}
                                                    className={cn(
                                                        'w-full h-full object-cover transition-all duration-300',
                                                        'grayscale group-hover:grayscale-0 transform group-hover:scale-110'
                                                    )}
                                                />
                                            </div>
                                            <p className="text-sm font-medium text-center text-foreground group-hover:text-primary transition-colors">
                                                {childCategory.name}
                                            </p>
                                            {childCategory.products_count > 0 && (
                                                <span className="text-xs text-muted-foreground">
                                            {childCategory.products_count} products
                                        </span>
                                            )}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Filters and Sorting */}
                        <div
                            className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 p-6 bg-card rounded-lg border">
                            {/* Search */}
                            <div className="w-full sm:w-auto">
                                <input
                                    type="text"
                                    placeholder="Search products..."
                                    value={localFilters.search || ''}
                                    onChange={(e) => handleFilterChange('search', e.target.value)}
                                    className="w-full sm:w-64 px-4 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                />
                            </div>

                            {/* Sort */}
                            <div className="flex items-center gap-4 w-full sm:w-auto">
                                <label className="text-sm font-medium text-foreground whitespace-nowrap">
                                    Sort by:
                                </label>
                                <select
                                    value={sortBy || 'newest'} // Ensure value is never undefined
                                    onChange={(e) => handleSortChange(e.target.value)}
                                    className="px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                >
                                    <option value="newest">Newest</option>
                                    <option value="name">Name</option>
                                    <option value="price_low">Price: Low to High</option>
                                    <option value="price_high">Price: High to Low</option>
                                    <option value="popular">Most Popular</option>
                                </select>
                            </div>
                        </div>

                        {/* Products Grid */}
                        {products && products.length > 0 ? (
                            <>
                                <div
                                    className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                                    {products.map((product) => (
                                        <ProductCard key={product.id} product={product} />
                                    ))}
                                </div>

                                {/* Pagination */}
                                <PaginationBottom pagination={pagination} />
                            </>
                        ) : (
                            /* No Products Message */
                            <div className="text-center py-16">
                                <div className="text-muted-foreground text-lg mb-4">
                                    No products found in this category.
                                </div>
                                {localFilters.search && (
                                    <button
                                        onClick={() => updateFilters({})}
                                        className="text-primary hover:text-primary/80 font-medium"
                                    >
                                        Clear filters
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </section>
        </PublicLayout>
);
}
// Product Card Component (you might already have this)
// function ProductCard({ product }) {
//     return (
//         <div className="group cursor-pointer bg-card border border-border rounded-lg overflow-hidden hover:shadow-lg transition-all duration-300">
//             <Link href={route('products.show', product.slug)}>
//                 <div className="aspect-square overflow-hidden bg-muted">
//                     <img
//                         src={product.image || product.media?.[0]?.url || "/placeholder.svg"}
//                         alt={product.name}
//                         className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
//                     />
//                 </div>
//                 <div className="p-4">
//                     <h3 className="font-semibold text-foreground mb-2 line-clamp-2 group-hover:text-primary transition-colors">
//                         {product.name}
//                     </h3>
//                     <p className="text-muted-foreground text-sm mb-3 line-clamp-2">
//                         {product.description}
//                     </p>
//                     <div className="flex items-center justify-between">
//                         <span className="text-lg font-bold text-foreground">
//                             ${product.price}
//                         </span>
//                         {product.brand && (
//                             <span className="text-xs text-muted-foreground bg-accent px-2 py-1 rounded">
//                                 {product.brand.name}
//                             </span>
//                         )}
//                     </div>
//                 </div>
//             </Link>
//         </div>
//     );
// }
