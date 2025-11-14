'use client'
import Title from '@/components/e-commerce/public/title';
import { useSelector } from 'react-redux'
// import ProductCard from '@/components/e-commerce/public/productCard';
import { useEffect, useState } from 'react';
import apiClient from '@/lib/apiClient';
import { Skeleton } from '@/components/ui/skeleton';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { route } from 'ziggy-js';
import { Link } from '@inertiajs/react';

const BestSelling = () => {

    const displayQuantity = 8
    // const displayQuantity = 6;
    // If you're fetching via API instead of Redux, use this:

    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchLatestProducts = async () => {
            try {
                setLoading(true);
                const response = await apiClient.get('/api/v1/best-selling-products');
                setProducts(response.data.products);
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };

        fetchLatestProducts();
    }, []);

    const bestSellingProducts = products.slice(0, displayQuantity);

    if (loading) {
        return (
            <section className="bg-background py-8 sm:py-16 lg:py-24">
                <div className="mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8">
                    <div className="space-y-4">
                        <Skeleton className="h-8 w-64" />
                        <Skeleton className="h-4 w-96" />
                    </div>
                    <div className="grid grid-cols-2 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        {Array.from({ length: displayQuantity }).map((_, index) => (
                            <Card key={index} className="overflow-hidden">
                                <CardHeader className="p-0">
                                    <Skeleton className="h-48 w-full" />
                                </CardHeader>
                                <CardContent className="p-4 space-y-2">
                                    <Skeleton className="h-4 w-3/4" />
                                    <Skeleton className="h-4 w-1/2" />
                                    <Skeleton className="h-6 w-1/3" />
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            </section>
        );
    }

    return (
        <section className="bg-background py-8 sm:py-16 lg:py-24">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8">
                {/* Title Section */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="space-y-2">
                        <h2 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                            Best Selling Products
                        </h2>
                        <p className="text-lg text-muted-foreground">
                            Our most popular products loved by customers
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={route('front.products')}>
                            View All Products
                        </Link>
                    </Button>
                </div>

                {/* Products Grid */}
                <div className="grid grid-cols-2 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {bestSellingProducts.map((product, index) => (
                        <ProductCard key={product.id} product={product} rank={index + 1} />
                    ))}
                </div>

                {bestSellingProducts.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-muted-foreground text-lg">No best selling products found.</p>
                    </div>
                )}
            </div>
        </section>
    );
}

// Product Card with sales rank
const ProductCard = ({ product, rank }) => {
    const formatPrice = (price) => {
        if (!price || price === "0.0000") return "Price on request";
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(parseFloat(price));
    };

    return (
        <Card className="group overflow-hidden hover:shadow-lg transition-all duration-300">
            <CardHeader className="p-0 relative">
                {/* Product Image */}
                <div className="aspect-square w-full bg-muted flex items-center justify-center">
                    <div className="text-muted-foreground text-sm text-center p-4">
                        No image available
                    </div>
                </div>

                {/* Sales Rank Badge */}
                <Badge className="absolute top-2 left-2 bg-primary">
                    #{rank} Best Seller
                </Badge>

                {/* Total Sold Badge */}
                {product.total_sold > 0 && (
                    <Badge variant="secondary" className="absolute top-2 right-2">
                        {product.total_sold} sold
                    </Badge>
                )}
            </CardHeader>

            <CardContent className="p-4 space-y-2">
                {/* Supplier */}
                {product.supplier && (
                    <p className="text-xs text-muted-foreground uppercase tracking-wide">
                        {product.supplier.name}
                    </p>
                )}

                {/* Product Name */}
                <CardTitle className="text-sm md:text-lg line-clamp-2 group-hover:text-primary transition-colors">
                    {product.name}
                </CardTitle>

                {/* Product Code */}
                {product.code && (
                    <CardDescription className="text-sm">
                        Code: {product.code}
                    </CardDescription>
                )}
            </CardContent>

            <CardFooter className="p-4 pt-0 flex items-center justify-between">
                <div className="space-y-1">
                    <p className="text-sm md:text-lg font-semibold text-foreground">
                        {formatPrice(product.price)}
                    </p>
                </div>

                <Button size="sm" asChild>
                    <Link href={`/product/${product.slug || product.id}`}>
                        View Details
                    </Link>
                </Button>
            </CardFooter>
        </Card>
    );
};

export default BestSelling;
