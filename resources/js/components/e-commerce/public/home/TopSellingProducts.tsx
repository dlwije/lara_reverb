// components/Home/TopSellingProducts.tsx
import { useEffect, useState } from 'react';
import apiClient from '@/lib/apiClient';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, TrendingUp, ShoppingCart, Star } from 'lucide-react';
import { TopSellingProduct } from '@/types/eCommerce/homepage';
import { ApiResponse } from '@/types/chat';
import { Link } from '@inertiajs/react';

export function TopSellingProducts() {
    const [products, setProducts] = useState<TopSellingProduct[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchTopSelling = async () => {
            try {
                setLoading(true);
                const response = await apiClient.get<ApiResponse<TopSellingProduct[]>>('/api/v1/products/top-selling');

                if (response.data.status) {
                    setProducts(response.data.data);
                }
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Failed to load top selling products');
            } finally {
                setLoading(false);
            }
        };

        fetchTopSelling();
    }, []);

    if (loading) {
        return (
            <section className="bg-background py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-4">
                        <Skeleton className="h-12 w-96 mx-auto rounded-xl bg-muted" />
                        <Skeleton className="h-6 w-64 mx-auto rounded-lg bg-muted" />
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {Array.from({ length: 8 }).map((_, index) => (
                            <ProductCardSkeleton key={index} />
                        ))}
                    </div>
                </div>
            </section>
        );
    }

    if (error) {
        return (
            <section className="bg-background py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                </div>
            </section>
        );
    }

    return (
        <section className="bg-background py-12 sm:py-16 lg:py-20">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                <div className="text-center space-y-4">
                    <div className="flex items-center justify-center gap-2">
                        <TrendingUp className="h-8 w-8 text-green-500" />
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            Top Selling Products
                        </h2>
                    </div>
                    <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
                        Most popular items loved by our customers
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {products.map((item, index) => (
                        <TopSellingProductCard
                            key={item.product_id}
                            item={item}
                            rank={index + 1}
                        />
                    ))}
                </div>

                {products.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-muted-foreground">No sales data available yet.</p>
                    </div>
                )}
            </div>
        </section>
    );
}

interface TopSellingProductCardProps {
    item: TopSellingProduct;
    rank: number;
}

function TopSellingProductCard({ item, rank }: TopSellingProductCardProps) {
    const product = item.product;

    return (
        <Card className="border-border bg-card hover:shadow-lg transition-all duration-300 group overflow-hidden">
            <div className="relative">
                {/* Rank Badge */}
                <Badge className={`absolute top-2 left-2 z-10 ${
                    rank === 1 ? 'bg-yellow-500' :
                        rank === 2 ? 'bg-gray-400' :
                            rank === 3 ? 'bg-orange-500' : 'bg-blue-500'
                } text-white`}>
                    #{rank}
                </Badge>

                {/* Product Image */}
                <Link href={`/products/${product.slug}`}>
                    <div className="relative h-48 w-full bg-muted overflow-hidden">
                        <img
                            src={product.photo || '/images/placeholder-product.jpg'}
                            alt={product.name}
                            className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    </div>
                </Link>

                {/* Sales Info */}
                <div className="absolute top-2 right-2 bg-background/90 backdrop-blur-sm rounded-full px-2 py-1">
          <span className="text-xs font-semibold text-foreground">
            {item.total_quantity} sold
          </span>
                </div>
            </div>

            <CardContent className="p-4">
                {/* Product Info */}
                <div className="space-y-2 mb-3">
                    <Link href={`/products/${product.slug}`}>
                        <h3 className="font-semibold text-foreground line-clamp-2 group-hover:text-primary transition-colors">
                            {product.name}
                        </h3>
                    </Link>

                    {product.secondary_name && (
                        <p className="text-sm text-muted-foreground line-clamp-1">
                            {product.secondary_name}
                        </p>
                    )}
                </div>

                {/* Price and Actions */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
            <span className="text-lg font-bold text-foreground">
              ${product.price}
            </span>
                        <div className="flex items-center gap-1 text-xs text-muted-foreground">
                            <TrendingUp className="w-3 h-3" />
                            <span>${item.total_revenue.toLocaleString()} revenue</span>
                        </div>
                    </div>

                    <Button size="sm" className="bg-primary text-primary-foreground hover:bg-primary/90">
                        <ShoppingCart className="h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

function ProductCardSkeleton() {
    return (
        <Card className="border-border bg-card overflow-hidden">
            <CardContent className="p-0">
                <Skeleton className="w-full h-48 bg-muted" />
                <div className="p-4 space-y-3">
                    <Skeleton className="h-4 w-3/4 bg-muted" />
                    <Skeleton className="h-4 w-1/2 bg-muted" />
                    <div className="flex justify-between items-center">
                        <Skeleton className="h-6 w-20 bg-muted" />
                        <Skeleton className="h-9 w-9 bg-muted rounded-full" />
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
