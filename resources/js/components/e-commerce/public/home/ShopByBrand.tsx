// components/Home/ShopByBrand.tsx
import { useEffect, useState } from 'react';
import apiClient from '@/lib/apiClient';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, Building2 } from 'lucide-react';
import { Brand } from '@/types/eCommerce/homepage';
import { ApiResponse } from '@/types/chat';
import { Link } from '@inertiajs/react';

export function ShopByBrand() {
    const [brands, setBrands] = useState<Brand[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchBrands = async () => {
            try {
                setLoading(true);
                const response = await apiClient.get<ApiResponse>('/api/v1/brands/featured');

                if (response.data.status) {
                    setBrands(response.data.data);
                }
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Failed to load brands');
            } finally {
                setLoading(false);
            }
        };

        fetchBrands();
    }, []);

    if (loading) {
        return (
            <section className="bg-muted/30 py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-4">
                        <Skeleton className="h-12 w-96 mx-auto rounded-xl bg-background" />
                        <Skeleton className="h-6 w-64 mx-auto rounded-lg bg-background" />
                    </div>
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                        {Array.from({ length: 12 }).map((_, index) => (
                            <BrandCardSkeleton key={index} />
                        ))}
                    </div>
                </div>
            </section>
        );
    }

    if (error) {
        return (
            <section className="bg-muted/30 py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                </div>
            </section>
        );
    }

    if (brands.length === 0) {
        return null;
    }

    return (
        <section className="bg-muted/30 py-12 sm:py-16 lg:py-20">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                <div className="text-center space-y-4">
                    <div className="flex items-center justify-center gap-2">
                        <Building2 className="h-8 w-8 text-blue-500" />
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            Shop by Brand
                        </h2>
                    </div>
                    <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
                        Discover products from your favorite brands
                    </p>
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                    {brands.map((brand) => (
                        <BrandCard key={brand.id} brand={brand} />
                    ))}
                </div>
            </div>
        </section>
    );
}

interface BrandCardProps {
    brand: Brand;
}

function BrandCard({ brand }: BrandCardProps) {
    return (
        <Card className="border-border bg-background hover:shadow-md transition-all duration-300 group">
            <Link href={`/brands/${brand.slug}`}>
                <CardContent className="p-6 flex flex-col items-center justify-center space-y-3 h-32">
                    {brand.photo ? (
                        <img
                            src={brand.photo}
                            alt={brand.name}
                            className="max-h-12 max-w-full object-contain opacity-80 group-hover:opacity-100 transition-opacity"
                        />
                    ) : (
                        <div className="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                            <Building2 className="w-6 h-6 text-primary" />
                        </div>
                    )}

                    <div className="text-center">
                        <h3 className="font-semibold text-sm text-foreground group-hover:text-primary transition-colors line-clamp-2">
                            {brand.name}
                        </h3>
                        {brand.products_count && (
                            <p className="text-xs text-muted-foreground mt-1">
                                {brand.products_count} products
                            </p>
                        )}
                    </div>
                </CardContent>
            </Link>
        </Card>
    );
}

function BrandCardSkeleton() {
    return (
        <Card className="border-border bg-background">
            <CardContent className="p-6 flex flex-col items-center justify-center space-y-3 h-32">
                <Skeleton className="w-12 h-12 rounded-full bg-muted" />
                <Skeleton className="h-4 w-16 bg-muted" />
                <Skeleton className="h-3 w-12 bg-muted" />
            </CardContent>
        </Card>
    );
}
