// components/Home/Promotions.tsx
import { useEffect, useState } from 'react';
import apiClient from '@/lib/apiClient';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, Tag, Calendar, Clock } from 'lucide-react';
import { Promotion } from '@/types/eCommerce/homepage';
import { ApiResponse } from '@/types/chat';
import { Link } from '@inertiajs/react';

export function Promotions() {
    const [promotions, setPromotions] = useState<Promotion[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchPromotions = async () => {
            try {
                setLoading(true);
                const response = await apiClient.get<ApiResponse>('/api/v1/promotions/active');

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
            <section className="bg-gradient-to-br from-primary/5 to-secondary/5 py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-4">
                        <Skeleton className="h-12 w-96 mx-auto rounded-xl bg-primary/10" />
                        <Skeleton className="h-6 w-64 mx-auto rounded-lg bg-primary/10" />
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {Array.from({ length: 3 }).map((_, index) => (
                            <PromotionCardSkeleton key={index} />
                        ))}
                    </div>
                </div>
            </section>
        );
    }

    if (error) {
        return (
            <section className="bg-gradient-to-br from-primary/5 to-secondary/5 py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                </div>
            </section>
        );
    }

    if (promotions.length === 0) {
        return null;
    }

    return (
        <section className="bg-gradient-to-br from-primary/5 to-secondary/5 py-12 sm:py-16 lg:py-20">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                <div className="text-center space-y-4">
                    <div className="flex items-center justify-center gap-2">
                        <Tag className="h-8 w-8 text-primary" />
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            Special Offers
                        </h2>
                    </div>
                    <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
                        Don't miss out on these amazing deals and promotions
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {promotions.map((promotion) => (
                        <PromotionCard key={promotion.id} promotion={promotion} />
                    ))}
                </div>
            </div>
        </section>
    );
}

interface PromotionCardProps {
    promotion: Promotion;
}

function PromotionCard({ promotion }: PromotionCardProps) {
    const getDiscountText = () => {
        if (promotion.discount && promotion.discount_method === 'percentage') {
            return `${promotion.discount}% OFF`;
        } else if (promotion.discount && promotion.discount_method === 'fixed') {
            return `$${promotion.discount} OFF`;
        }
        return 'Special Offer';
    };

    const isActive = promotion.active &&
        (!promotion.start_date || new Date(promotion.start_date) <= new Date()) &&
        (!promotion.end_date || new Date(promotion.end_date) >= new Date());

    return (
        <Card className="border-border bg-background hover:shadow-lg transition-all duration-300 group overflow-hidden">
            <CardContent className="p-6 space-y-4">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <Badge variant={isActive ? "default" : "secondary"} className={
                        isActive ? "bg-green-500 hover:bg-green-600" : ""
                    }>
                        {isActive ? 'Active' : 'Inactive'}
                    </Badge>

                    <Badge variant="outline" className="bg-primary/10 text-primary border-primary/20">
                        {getDiscountText()}
                    </Badge>
                </div>

                {/* Promotion Details */}
                <div className="space-y-3">
                    <h3 className="font-bold text-lg text-foreground group-hover:text-primary transition-colors">
                        {promotion.name}
                    </h3>

                    <p className="text-sm text-muted-foreground">
                        {promotion.type.replace('_', ' ').toUpperCase()}
                    </p>

                    {/* Categories */}
                    {promotion.categories && promotion.categories.length > 0 && (
                        <div className="flex flex-wrap gap-1">
                            {promotion.categories.slice(0, 3).map((category) => (
                                <Badge key={category.id} variant="outline" className="text-xs">
                                    {category.name}
                                </Badge>
                            ))}
                            {promotion.categories.length > 3 && (
                                <Badge variant="outline" className="text-xs">
                                    +{promotion.categories.length - 3} more
                                </Badge>
                            )}
                        </div>
                    )}
                </div>

                {/* Dates */}
                <div className="space-y-2 text-sm">
                    {promotion.start_date && (
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <Calendar className="w-4 h-4" />
                            <span>Starts: {new Date(promotion.start_date).toLocaleDateString()}</span>
                        </div>
                    )}

                    {promotion.end_date && (
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <Clock className="w-4 h-4" />
                            <span>Ends: {new Date(promotion.end_date).toLocaleDateString()}</span>
                        </div>
                    )}
                </div>

                {/* Action */}
                <Button variant="outline" className="w-full" asChild>
                    <Link href={`/promotions/${promotion.id}`}>
                        View Details
                    </Link>
                </Button>
            </CardContent>
        </Card>
    );
}

function PromotionCardSkeleton() {
    return (
        <Card className="border-border bg-background overflow-hidden">
            <CardContent className="p-6 space-y-4">
                <div className="flex justify-between">
                    <Skeleton className="h-6 w-16 bg-muted" />
                    <Skeleton className="h-6 w-20 bg-muted" />
                </div>
                <div className="space-y-2">
                    <Skeleton className="h-6 w-3/4 bg-muted" />
                    <Skeleton className="h-4 w-1/2 bg-muted" />
                </div>
                <div className="space-y-2">
                    <Skeleton className="h-4 w-full bg-muted" />
                    <Skeleton className="h-4 w-2/3 bg-muted" />
                </div>
                <Skeleton className="h-10 w-full bg-muted" />
            </CardContent>
        </Card>
    );
}
