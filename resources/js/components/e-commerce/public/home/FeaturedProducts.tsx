// components/Home/FeaturedProducts.jsx
import { useEffect, useState } from 'react'
import apiClient from '@/lib/apiClient'
import { Skeleton } from '@/components/ui/skeleton'
import { Card, CardContent } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { AlertCircle, Star, ShoppingCart, Eye } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Link, usePage } from '@inertiajs/react';
import { number_format } from '@/Core';
import { formatCurrency } from '@/lib/e-commerce/amountHelper';

export function FeaturedProducts() {
    const [products, setProducts] = useState([])
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState(null)

    useEffect(() => {
        const fetchFeaturedProducts = async () => {
            try {
                setLoading(true)
                setError(null)
                const response = await apiClient.get('/api/v1/products/featured')
                // console.log(response.data.data)

                if (response.data.status && response.data.data) {
                    setProducts(response.data.data)
                } else {
                    setProducts([])
                }
            } catch (err) {
                setError(err.message)
                console.error('Error fetching featured products:', err)
            } finally {
                setLoading(false)
            }
        }

        fetchFeaturedProducts()
    }, [])

    // Loading State
    if (loading) {
        return (
            <section className="bg-background py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-4">
                        <Skeleton className="h-12 w-96 mx-auto rounded-xl bg-muted" />
                        <Skeleton className="h-6 w-64 mx-auto rounded-lg bg-muted" />
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
                        {Array.from({ length: 8 }).map((_, index) => (
                            <Card key={index} className="border-border bg-card overflow-hidden">
                                <CardContent className="p-0">
                                    <Skeleton className="w-full h-48 bg-muted" />
                                    <div className="p-4 space-y-3">
                                        <Skeleton className="h-4 w-3/4 bg-muted" />
                                        <Skeleton className="h-4 w-1/2 bg-muted" />
                                        <div className="flex justify-between items-center">
                                            <Skeleton className="h-6 w-20 bg-muted" />
                                            <Skeleton className="h-9 w-24 bg-muted" />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            </section>
        )
    }

    // Error State
    if (error) {
        return (
            <section className="bg-background py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-8">
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            Featured Products
                        </h2>

                        <Alert variant="destructive" className="max-w-md mx-auto">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>
                                Failed to load featured products. Please try again.
                            </AlertDescription>
                        </Alert>

                        <Button
                            onClick={() => window.location.reload()}
                            variant="outline"
                        >
                            Reload Page
                        </Button>
                    </div>
                </div>
            </section>
        )
    }

    // Empty State
    if (products.length === 0 && !loading) {
        return (
            <section className="bg-background py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-8">
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            Featured Products
                        </h2>

                        <Card className="max-w-md mx-auto border-border bg-card">
                            <CardContent className="pt-6">
                                <div className="flex flex-col items-center gap-4 text-muted-foreground">
                                    <Star className="h-12 w-12" />
                                    <p className="text-lg font-medium">No featured products</p>
                                    <p className="text-sm">Check back later for featured items</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>
        )
    }

    return (
        <section className="bg-background">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="text-center space-y-4">
                    <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                        Featured Products
                    </h2>
                    <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
                        Handpicked items just for you
                    </p>
                </div>

                {/* Products Grid */}
                <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
                    {products.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </div>
        </section>
    )
}

// Product Card Component
function ProductCard({ product }) {
    const [imageLoaded, setImageLoaded] = useState(false)
    const isOnSale = product.on_sale && product.original_price > product.price

    return (
        <Card className="border-border bg-card hover:shadow-lg transition-all duration-300 group overflow-hidden">
            <div className="relative overflow-hidden">
                {/* Product Image */}
                <Link href={`/product/${product.slug}`}>
                    <div className="relative h-48 w-full bg-muted overflow-hidden">
                        {!imageLoaded && (
                            <Skeleton className="w-full h-full bg-muted" />
                        )}
                        <img
                            src={product.photo || '/placeholder.svg'}
                            alt={product.name}
                            className={`w-full h-full object-cover transition-transform duration-300 group-hover:scale-105 ${
                                imageLoaded ? 'block' : 'hidden'
                            }`}
                            onLoad={() => setImageLoaded(true)}
                        />

                        {/* Badges */}
                        <div className="absolute top-2 left-2 flex flex-col gap-1">
                            {product.featured && (
                                <Badge className="bg-primary text-primary-foreground">
                                    Featured
                                </Badge>
                            )}
                            {isOnSale && (
                                <Badge variant="destructive">
                                    Sale
                                </Badge>
                            )}
                        </div>
                    </div>
                </Link>

                {/* Quick Actions */}
                <div className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col gap-1">
                    <Button size="icon" variant="secondary" className="h-8 w-8 bg-background/80 backdrop-blur-sm">
                        <Eye className="h-4 w-4" />
                    </Button>
                    <Button size="icon" variant="secondary" className="h-8 w-8 bg-background/80 backdrop-blur-sm">
                        <ShoppingCart className="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <CardContent className="p-4">
                {/* Brand */}
                {product.brand && (
                    <p className="text-xs text-muted-foreground mb-1">{product.brand}</p>
                )}

                {/* Product Name */}
                <Link href={`/product/${product.slug}`}>
                    <h3 className="font-semibold text-foreground mb-2 line-clamp-2 group-hover:text-primary transition-colors">
                        {product.name}
                    </h3>
                </Link>

                {/* Secondary Name */}
                {product.secondary_name && (
                    <p className="text-sm text-muted-foreground mb-3 line-clamp-1">
                        {product.secondary_name}
                    </p>
                )}

                {/* Price */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <span className="text-lg font-bold text-foreground">
                            {formatCurrency(product.price)}
                        </span>
                        {isOnSale && (
                            <span className="text-sm text-muted-foreground line-through">
                                {formatCurrency(product.original_price)}
                            </span>
                        )}
                    </div>

                    <Button variant="ghost" size="sm" className="size-9 transition-all duration-200 cursor-pointer">
                        <ShoppingCart className="h-4 w-4 mr-1" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    )
}
