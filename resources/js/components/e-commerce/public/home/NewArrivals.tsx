// components/Home/NewArrivals.jsx
import { useEffect, useState } from 'react'
import apiClient from '@/lib/apiClient'
import { Skeleton } from '@/components/ui/skeleton'
import { Card, CardContent } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { AlertCircle, Calendar, ShoppingCart } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Link } from '@inertiajs/react';
import { formatCurrency } from '@/lib/e-commerce/amountHelper';

export function NewArrivals() {
    const [products, setProducts] = useState([])
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState(null)

    useEffect(() => {
        const fetchNewArrivals = async () => {
            try {
                setLoading(true)
                setError(null)
                const response = await apiClient.get('/api/v1/products/new-arrivals')

                if (response.data.status && response.data.data) {
                    setProducts(response.data.data)
                } else {
                    setProducts([])
                }
            } catch (err) {
                setError(err.message)
                console.error('Error fetching new arrivals:', err)
            } finally {
                setLoading(false)
            }
        }

        fetchNewArrivals()
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

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                        {Array.from({ length: 10 }).map((_, index) => (
                            <Card key={index} className="border-border bg-card overflow-hidden">
                                <CardContent className="p-0">
                                    <Skeleton className="w-full h-40 bg-muted" />
                                    <div className="p-3 space-y-2">
                                        <Skeleton className="h-4 w-full bg-muted" />
                                        <Skeleton className="h-4 w-2/3 bg-muted" />
                                        <Skeleton className="h-6 w-16 bg-muted" />
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
                            New Arrivals
                        </h2>

                        <Alert variant="destructive" className="max-w-md mx-auto">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>
                                Failed to load new arrivals. Please try again.
                            </AlertDescription>
                        </Alert>
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
                            New Arrivals
                        </h2>

                        <Card className="max-w-md mx-auto border-border bg-card">
                            <CardContent className="pt-6">
                                <div className="flex flex-col items-center gap-4 text-muted-foreground">
                                    <Calendar className="h-12 w-12" />
                                    <p className="text-lg font-medium">No new arrivals</p>
                                    <p className="text-sm">Check back soon for new products</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>
        )
    }

    return (
        <section className="bg-background py-12 sm:py-16 lg:py-20">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="text-center space-y-4">
                    <div className="flex items-center justify-center gap-2">
                        <Calendar className="h-8 w-8 text-blue-500" />
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            New Arrivals
                        </h2>
                    </div>
                    <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
                        Discover our latest products just added to the store
                    </p>
                </div>

                {/* Products Grid */}
                <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    {products.map((product) => (
                        <NewArrivalCard key={product.id} product={product} />
                    ))}
                </div>
            </div>
        </section>
    )
}

// New Arrival Card Component
function NewArrivalCard({ product }) {
    const isNew = isProductNew(product.created_at)

    function isProductNew(createdAt) {
        const created = new Date(createdAt)
        const now = new Date()
        const diffTime = Math.abs(now - created)
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
        return diffDays <= 7 // Consider products as "new" for 7 days
    }

    return (
        <Card className="border-border bg-card hover:shadow-md transition-all duration-300 group overflow-hidden">
            <div className="relative">
                {/* Product Image */}
                <Link href={`/product/${product.slug}`}>
                    <div className="relative h-40 w-full bg-muted overflow-hidden">
                        <img
                            src={product.photo || '/placeholder.svg'}
                            alt={product.name}
                            className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />

                        {/* New Badge */}
                        {isNew && (
                            <Badge className="absolute top-2 left-2 bg-green-500 text-white">
                                New
                            </Badge>
                        )}
                    </div>
                </Link>
            </div>

            <CardContent className="p-3">
                {/* Product Name */}
                <Link href={`/product/${product.slug}`}>
                    <h3 className="font-medium text-sm text-foreground mb-2 line-clamp-2 group-hover:text-primary transition-colors">
                        {product.name}
                    </h3>
                </Link>

                {/* Price */}
                <div className="flex items-center justify-between">
                    <span className="text-lg font-bold text-foreground">
                        {formatCurrency(product.price)}
                    </span>

                    <Button variant={`ghost`} size="sm" className="size-9 transition-all duration-200 cursor-pointer">
                        <ShoppingCart className="h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    )
}
