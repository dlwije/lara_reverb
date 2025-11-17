// components/Home/BrandsShowcase.jsx
import { useEffect, useState } from 'react'
import apiClient from '@/lib/apiClient'
import { Skeleton } from '@/components/ui/skeleton'
import { Card, CardContent } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { AlertCircle, Crown } from 'lucide-react'
import { Link } from '@inertiajs/react';

export function BrandsShowcase() {
    const [brands, setBrands] = useState([])
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState(null)

    useEffect(() => {
        const fetchBrands = async () => {
            try {
                setLoading(true)
                setError(null)
                const response = await apiClient.get('/api/v1/home/brands/featured')

                if (response.data.status && response.data.data) {
                    setBrands(response.data.data)
                } else {
                    setBrands([])
                }
            } catch (err) {
                setError(err.message)
                console.error('Error fetching brands:', err)
            } finally {
                setLoading(false)
            }
        }

        fetchBrands()
    }, [])

    // Loading State
    if (loading) {
        return (
            <section className="bg-muted/30 py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-4">
                        <Skeleton className="h-12 w-96 mx-auto rounded-xl bg-background" />
                        <Skeleton className="h-6 w-64 mx-auto rounded-lg bg-background" />
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6 md:gap-8">
                        {Array.from({ length: 12 }).map((_, index) => (
                            <Card key={index} className="border-border bg-background">
                                <CardContent className="p-6 flex items-center justify-center">
                                    <Skeleton className="w-20 h-8 bg-muted rounded" />
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
            <section className="bg-muted/30 py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-8">
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            Popular Brands
                        </h2>

                        <Alert variant="destructive" className="max-w-md mx-auto">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>
                                Failed to load brands. Please try again.
                            </AlertDescription>
                        </Alert>
                    </div>
                </div>
            </section>
        )
    }

    // Empty State
    if (brands.length === 0 && !loading) {
        return null // Don't show section if no brands
    }

    return (
        <section className="bg-muted/30 py-12 sm:py-16 lg:py-20">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="text-center space-y-4">
                    <div className="flex items-center justify-center gap-2">
                        <Crown className="h-8 w-8 text-yellow-500" />
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            Popular Brands
                        </h2>
                    </div>
                    <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
                        Shop from your favorite trusted brands
                    </p>
                </div>

                {/* Brands Grid */}
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6 md:gap-8">
                    {brands.map((brand) => (
                        <BrandCard key={brand.id} brand={brand} />
                    ))}
                </div>
            </div>
        </section>
    )
}

// Brand Card Component
function BrandCard({ brand }) {
    return (
        <Card className="border-border bg-background hover:shadow-md transition-all duration-300 group">
            <Link href={`/brands/${brand.slug}`}>
                <CardContent className="p-6 flex items-center justify-center h-24">
                    {brand.logo ? (
                        <img
                            src={brand.logo}
                            alt={brand.name}
                            className="max-h-12 max-w-full object-contain opacity-80 group-hover:opacity-100 transition-opacity"
                        />
                    ) : (
                        <span className="font-semibold text-foreground group-hover:text-primary transition-colors">
                            {brand.name}
                        </span>
                    )}
                </CardContent>
            </Link>
        </Card>
    )
}
