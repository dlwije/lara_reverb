// components/Home/HeroBanner.tsx
import { useEffect, useState } from 'react';
import apiClient from '@/lib/apiClient';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { AlertCircle, ArrowRight, Tag } from 'lucide-react';
import { ApiResponse } from '@/types/chat';
import { Link } from '@inertiajs/react';
import { Promotion } from '@/types/eCommerce/homepage';
import { assets } from '../../../../../../public/e-commerce/assets/assets';

export function HeroBanner() {
    const [promotions, setPromotions] = useState<Promotion[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchPromotions = async () => {
            try {
                setLoading(true);
                const response = await apiClient.get<ApiResponse>('/api/v1/home/promotions/active');

                if (response.data.status) {
                    setPromotions(response.data.data);
                }
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Failed to load promotions');
            } finally {
                setLoading(false);
            }
        };

        fetchPromotions();
    }, []);

    if (loading) {
        return (
            <section className="relative bg-gradient-to-r from-primary/10 to-primary/5 py-20 lg:py-32">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid lg:grid-cols-2 gap-12 items-center">
                        <div className="space-y-6">
                            <Skeleton className="h-12 w-3/4 bg-primary/20 rounded-xl" />
                            <Skeleton className="h-6 w-full bg-primary/20 rounded-lg" />
                            <Skeleton className="h-6 w-2/3 bg-primary/20 rounded-lg" />
                            <div className="flex gap-4">
                                <Skeleton className="h-12 w-32 bg-primary/20 rounded-lg" />
                                <Skeleton className="h-12 w-40 bg-primary/20 rounded-lg" />
                            </div>
                        </div>
                        <Skeleton className="h-80 w-full bg-primary/20 rounded-2xl" />
                    </div>
                </div>
            </section>
        );
    }

    return (
        <section className="relative bg-gradient-to-r from-primary/10 via-background to-primary/5 py-20 lg:py-32 overflow-hidden">
            <div className="absolute inset-0 bg-grid-slate-900/[0.04] bg-[size:60px_60px]" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative">
                <div className="grid lg:grid-cols-2 gap-12 items-center">
                    {/* Content */}
                    <div className="space-y-8">
                        <div className="space-y-4">
                            {promotions.length > 0 && (
                                <Badge variant="secondary" className="bg-primary/10 text-primary border-primary/20">
                                    <Tag className="w-4 h-4 mr-1" />
                                    {promotions[0].name}
                                </Badge>
                            )}

                            <h1 className="text-4xl lg:text-6xl font-bold tracking-tight text-foreground">
                                Welcome to Our{' '}
                                <span className="bg-gradient-to-r from-primary to-primary/60 bg-clip-text text-transparent">
                  E-Commerce
                </span>
                            </h1>

                            <p className="text-xl text-muted-foreground max-w-2xl">
                                Discover amazing products at unbeatable prices. Shop from thousands of items with fast delivery and excellent customer service.
                            </p>
                        </div>

                        <div className="flex flex-col sm:flex-row gap-4">
                            <Button size="lg" className="bg-primary text-primary-foreground hover:bg-primary/90">
                                Shop Now
                                <ArrowRight className="ml-2 w-4 h-4" />
                            </Button>
                            <Button size="lg" variant="outline" asChild>
                                <Link href="/categories">Browse Categories</Link>
                            </Button>
                        </div>

                        {/* Stats */}
                        <div className="grid grid-cols-3 gap-8 pt-8 border-t border-border">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-foreground">10K+</div>
                                <div className="text-sm text-muted-foreground">Products</div>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold text-foreground">500+</div>
                                <div className="text-sm text-muted-foreground">Brands</div>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold text-foreground">50K+</div>
                                <div className="text-sm text-muted-foreground">Customers</div>
                            </div>
                        </div>
                    </div>

                    {/* Hero Image */}
                    <div className="relative">
                        <div className="relative rounded-2xl bg-background/80 backdrop-blur-sm border border-border p-8 shadow-2xl">
                            <img
                                src={assets.hero_model_img}
                                alt="E-commerce Showcase"
                                className="w-full h-auto rounded-lg shadow-lg"
                            />

                            {/* Floating elements */}
                            <div className="absolute -top-4 -left-4 bg-primary text-primary-foreground px-4 py-2 rounded-lg shadow-lg">
                                <div className="text-sm font-semibold">Sale</div>
                                <div className="text-xs">Up to 50% Off</div>
                            </div>

                            <div className="absolute -bottom-4 -right-4 bg-secondary text-secondary-foreground px-4 py-2 rounded-lg shadow-lg">
                                <div className="text-sm font-semibold">Free</div>
                                <div className="text-xs">Shipping</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
