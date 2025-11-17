// Product Card with sales rank
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import React from 'react';
import { Product } from '@/types/product';

interface PageProps {
    product: Product;
    rank: number;
}
const BestSellingProductCard:React.FC<PageProps> = ({ product, rank }) => {
    const { id, name, slug, code, price, cost, description, stocks, supplier, category, on_sale, active, photo } = product;
    const formatPrice = (price:number) => {
        if (!price || price === "0.0000") return "Price on request";
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'AED'
        }).format(parseFloat(price));
    };

    return (
        <Card className="group overflow-hidden hover:shadow-lg transition-all duration-300">
            <CardHeader className="p-0 relative">
                {/* Product Image */}
                <Link href={`/product/${slug}`} className="group block max-xl:mx-auto">
                    <div className="aspect-square w-full bg-muted flex items-center justify-center">
                        {photo ? (
                            <img src={photo} alt={name} className="h-full w-full rounded-lg object-cover" />
                        ) : (
                            <img src={'/placeholder.svg'} alt={'0360_product_placeholder'}
                                 className="h-full w-full rounded-lg object-cover" />
                        )}
                    </div>
                </Link>

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
                {supplier && (
                    <p className="text-xs text-muted-foreground uppercase tracking-wide">
                        {supplier.name}
                    </p>
                )}

                {/* Product Name */}
                <CardTitle className="line-clamp-1 sm:line-clamp-2 text-sm md:text-lg group-hover:text-primary transition-colors">
                    {name}
                </CardTitle>

                {/* Product Code */}
                {code && (
                    <CardDescription className="text-sm">
                        Code: {code}
                    </CardDescription>
                )}
            </CardContent>

            <CardFooter className="p-4 pt-0 flex items-center justify-between">
                <div className="space-y-1">
                    <p className="text-xs sm:text-sm md:text-lg font-semibold text-foreground">
                        {formatPrice(price)}
                    </p>
                </div>

                <Button size="sm" asChild>
                    <Link href={`/product/${slug || id}`}>
                        View Details
                    </Link>
                </Button>
            </CardFooter>
        </Card>
    );
};

export default BestSellingProductCard;
