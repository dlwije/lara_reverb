// components/Home/HotDeals.jsx
import { useEffect, useState } from 'react'
import apiClient from '@/lib/apiClient'
import { Skeleton } from '@/components/ui/skeleton'
import { Card, CardContent } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { AlertCircle, Clock, Zap } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Link } from '@inertiajs/react';
import { formatCurrency } from '@/lib/e-commerce/amountHelper';

export function HotDeals() {
    const [deals, setDeals] = useState([])
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState(null)

    useEffect(() => {
        const fetchHotDeals = async () => {
            try {
                setLoading(true)
                setError(null)
                const response = await apiClient.get('/api/v1/products/hot-deals')

                if (response.data.status && response.data.data) {
                    setDeals(response.data.data)
                } else {
                    setDeals([])
                }
            } catch (err) {
                setError(err.message)
                console.error('Error fetching hot deals:', err)
            } finally {
                setLoading(false)
            }
        }

        fetchHotDeals()
    }, [])

    // Loading State
    if (loading) {
        return (
            <section className="bg-muted/50 py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-4">
                        <Skeleton className="h-12 w-96 mx-auto rounded-xl bg-background" />
                        <Skeleton className="h-6 w-64 mx-auto rounded-lg bg-background" />
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                        {Array.from({ length: 3 }).map((_, index) => (
                            <Card key={index} className="border-border bg-background overflow-hidden">
                                <CardContent className="p-0">
                                    <Skeleton className="w-full h-48 bg-muted" />
                                    <div className="p-4 space-y-3">
                                        <Skeleton className="h-4 w-3/4 bg-muted" />
                                        <Skeleton className="h-2 w-full bg-muted" />
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
            <section className="bg-muted/50 py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-8">
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            Hot Deals
                        </h2>

                        <Alert variant="destructive" className="max-w-md mx-auto">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>
                                Failed to load hot deals. Please try again.
                            </AlertDescription>
                        </Alert>
                    </div>
                </div>
            </section>
        )
    }

    // Empty State
    if (deals.length === 0 && !loading) {
        return (
            <section className="bg-muted/50 py-12 sm:py-16 lg:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center space-y-8">
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            Hot Deals
                        </h2>

                        <Card className="max-w-md mx-auto border-border bg-background">
                            <CardContent className="pt-6">
                                <div className="flex flex-col items-center gap-4 text-muted-foreground">
                                    <Zap className="h-12 w-12" />
                                    <p className="text-lg font-medium">No hot deals available</p>
                                    <p className="text-sm">Check back later for exciting offers</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>
        )
    }

    return (
        <section className="bg-muted/50 py-12 sm:py-16 lg:py-20">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="text-center space-y-4">
                    <div className="flex items-center justify-center gap-2">
                        <Zap className="h-8 w-8 text-yellow-500" />
                        <h2 className="text-3xl md:text-4xl font-bold text-foreground">
                            Hot Deals
                        </h2>
                    </div>
                    <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
                        Limited time offers - Don't miss out!
                    </p>
                </div>

                {/* Deals Grid */}
                <div className="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                    {deals.map((deal) => (
                        <DealCard key={deal.id} deal={deal} />
                    ))}
                </div>
            </div>
        </section>
    )
}

// Deal Card Component
function DealCard({ deal }) {
    const [timeLeft, setTimeLeft] = useState(calculateTimeLeft(deal.end_date))
    const discount = Math.round(((deal.original_price - deal.price) / deal.original_price) * 100)

    useEffect(() => {
        if (!deal.end_date) return

        const timer = setInterval(() => {
            setTimeLeft(calculateTimeLeft(deal.end_date))
        }, 1000)

        return () => clearInterval(timer)
    }, [deal.end_date])

    function calculateTimeLeft(endDate) {
        if (!endDate) return null
        const difference = new Date(endDate) - new Date()

        if (difference <= 0) return null

        return {
            days: Math.floor(difference / (1000 * 60 * 60 * 24)),
            hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
            minutes: Math.floor((difference / 1000 / 60) % 60),
            seconds: Math.floor((difference / 1000) % 60)
        }
    }

    return (
        <Card className="border-border bg-background hover:shadow-lg transition-all duration-300 group overflow-hidden relative">
            {/* Discount Badge */}
            <Badge className="absolute top-2 left-2 z-10 bg-red-500 text-white">
                {discount}%
            </Badge>

            {/* Product Image */}
            <Link href={`/product/${deal.slug}`}>
                <div className="relative h-48 w-full bg-muted overflow-hidden">
                    <img
                        src={deal.photo || '/placeholder.svg'}
                        alt={deal.name}
                        className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                </div>
            </Link>

            <CardContent className="p-4">
                {/* Product Info */}
                <div className="space-y-2 mb-4">
                    <Link href={`/products/${deal.slug}`}>
                        <h3 className="font-semibold text-foreground line-clamp-2 group-hover:text-primary transition-colors">
                            {deal.name}
                        </h3>
                    </Link>

                    {/* Price */}
                    <div className="flex items-center gap-2">
                        <span className="text-xl font-bold text-foreground">
                            {formatCurrency(deal.price)}
                        </span>
                        <span className="text-sm text-muted-foreground line-through">
                            {formatCurrency(deal.original_price)}
                        </span>
                    </div>
                </div>

                {/* Countdown Timer */}
                {timeLeft && (
                    <div className="space-y-2">
                        <div className="flex items-center gap-1 text-sm text-muted-foreground">
                            <Clock className="h-4 w-4" />
                            <span>Ends in:</span>
                        </div>
                        <div className="flex gap-1 text-xs">
                            <div className="flex-1 text-center bg-muted rounded p-1">
                                {timeLeft.days}d
                            </div>
                            <div className="flex-1 text-center bg-muted rounded p-1">
                                {timeLeft.hours}h
                            </div>
                            <div className="flex-1 text-center bg-muted rounded p-1">
                                {timeLeft.minutes}m
                            </div>
                            <div className="flex-1 text-center bg-muted rounded p-1">
                                {timeLeft.seconds}s
                            </div>
                        </div>
                    </div>
                )}

                {/* Stock Progress */}
                {deal.stock_percentage && (
                    <div className="space-y-1 mt-3">
                        <div className="flex justify-between text-xs text-muted-foreground">
                            <span>Sold: {deal.sold_count}</span>
                            <span>Available: {deal.stock_available}</span>
                        </div>
                        <Progress value={deal.stock_percentage} className="h-2" />
                    </div>
                )}
            </CardContent>
        </Card>
    )
}
