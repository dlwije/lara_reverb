"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Slider } from "@/components/ui/slider"
import { Badge } from "@/components/ui/badge"
import { Heart, Star, SlidersHorizontal } from "lucide-react"
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from "@/components/ui/sheet"
import PublicLayout from '@/pages/e-commerce/public/layout';

const categories = ["All", "Accessories", "Electronics", "Sports"]

const features = [
    "Water Resistant",
    "Heart Rate Monitor",
    "GPS",
    "Sleep Tracking",
    "Notifications",
    "Leather Strap",
    "Steel Band",
]

const products = [
    {
        id: 1,
        name: "Classic Leather Watch",
        category: "Accessories",
        rating: 4.9,
        reviews: 128,
        price: 299,
        originalPrice: 399,
        image: "/white-elegant-leather-watch.jpg",
        tags: ["Water Resistant", "Leather Strap"],
    },
    {
        id: 2,
        name: "Smart Watch Pro",
        category: "Electronics",
        rating: 4.8,
        reviews: 256,
        price: 499,
        originalPrice: 599,
        image: "/white-smartwatch-technology.jpg",
        tags: ["Heart Rate Monitor", "GPS", "Sleep Tracking", "Notifications"],
    },
    {
        id: 3,
        name: "Sport Watch Elite",
        category: "Sports",
        rating: 4.7,
        reviews: 192,
        price: 399,
        originalPrice: 449,
        image: "/white-sports-watch.jpg",
        tags: ["Water Resistant", "Heart Rate Monitor", "GPS"],
    },
]

export default function ProductsPage() {
    const [selectedCategory, setSelectedCategory] = useState("All")
    const [priceRange, setPriceRange] = useState([0, 1000])
    const [minRating, setMinRating] = useState([0])
    const [selectedFeatures, setSelectedFeatures] = useState<string[]>([])
    const [searchQuery, setSearchQuery] = useState("")

    const toggleFeature = (feature: string) => {
        setSelectedFeatures((prev) => (prev.includes(feature) ? prev.filter((f) => f !== feature) : [...prev, feature]))
    }

    const FiltersContent = () => (
        <div className="space-y-8">
            {/* Search */}
            <div>
                <h3 className="mb-4 text-sm font-semibold text-foreground">Search</h3>
                <Input
                    placeholder="Search products..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="bg-background"
                />
            </div>

            {/* Category */}
            <div>
                <h3 className="mb-4 text-sm font-semibold text-foreground">Category</h3>
                <div className="grid grid-cols-2 gap-2">
                    {categories.map((category) => (
                        <Button
                            key={category}
                            variant={selectedCategory === category ? "default" : "outline"}
                            size="sm"
                            onClick={() => setSelectedCategory(category)}
                            className="justify-center"
                        >
                            {category}
                        </Button>
                    ))}
                </div>
            </div>

            {/* Price Range */}
            <div>
                <h3 className="mb-4 text-sm font-semibold text-foreground">Price Range</h3>
                <Slider value={priceRange} onValueChange={setPriceRange} max={1000} step={10} className="mb-3" />
                <div className="flex justify-between text-sm text-muted-foreground">
                    <span>${priceRange[0]}</span>
                    <span>${priceRange[1]}</span>
                </div>
            </div>

            {/* Minimum Rating */}
            <div>
                <h3 className="mb-4 text-sm font-semibold text-foreground">Minimum Rating</h3>
                <Slider value={minRating} onValueChange={setMinRating} max={5} step={0.1} className="mb-3" />
                <div className="flex items-center gap-1 text-muted-foreground">
                    <Star className="h-4 w-4 fill-current" />
                    <span className="text-sm">{minRating[0].toFixed(1)}</span>
                </div>
            </div>

            {/* Features */}
            <div>
                <h3 className="mb-4 text-sm font-semibold text-foreground">Features</h3>
                <div className="space-y-2">
                    {features.map((feature) => (
                        <Button
                            key={feature}
                            variant={selectedFeatures.includes(feature) ? "default" : "outline"}
                            size="sm"
                            onClick={() => toggleFeature(feature)}
                            className="w-full justify-center"
                        >
                            {feature}
                        </Button>
                    ))}
                </div>
            </div>
        </div>
    )

    return (
        <PublicLayout>
        <div className="min-h-screen bg-background">
            <div className="flex">
                <aside className="hidden w-[280px] border-r border-border bg-card p-6 lg:block">
                    <FiltersContent />
                </aside>

                {/* Main Content */}
                <main className="flex-1 p-4 md:p-8">
                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-base text-muted-foreground md:text-lg">{products.length} products found</h1>

                        <Sheet>
                            <SheetTrigger asChild>
                                <Button variant="outline" size="sm" className="lg:hidden bg-transparent">
                                    <SlidersHorizontal className="mr-2 h-4 w-4" />
                                    Filters
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="right" className="w-[300px] overflow-y-auto">
                                <SheetHeader className="mb-8">
                                    <SheetTitle>Filters</SheetTitle>
                                </SheetHeader>
                                <FiltersContent />
                            </SheetContent>
                        </Sheet>
                    </div>

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {products.map((product) => (
                            <Card key={product.id} className="group overflow-hidden bg-card flex flex-col">
                                <div className="relative aspect-square overflow-hidden bg-muted">
                                    <img
                                        src={product.image || "/placeholder.svg"}
                                        alt={product.name}
                                        className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    />
                                    <button className="absolute right-3 top-3 rounded-full bg-background p-2 shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100 hover:bg-accent">
                                        <Heart className="h-5 w-5 text-foreground" />
                                    </button>
                                </div>

                                <CardContent className="flex flex-1 flex-col p-6">
                                    <div className="flex-1 space-y-3">
                                        <div className="space-y-2">
                                            <h3 className="text-lg font-semibold text-foreground">{product.name}</h3>
                                            <Badge variant="secondary" className="w-fit bg-muted text-foreground">
                                                {product.category}
                                            </Badge>
                                        </div>

                                        <div className="flex items-center gap-1">
                                            <Star className="h-4 w-4 fill-current text-foreground" />
                                            <span className="font-semibold text-foreground">{product.rating}</span>
                                            <span className="text-sm text-muted-foreground">({product.reviews})</span>
                                        </div>

                                        <div className="flex items-baseline gap-2">
                                            <span className="text-2xl font-bold text-foreground">${product.price}</span>
                                            <span className="text-sm text-muted-foreground line-through">${product.originalPrice}</span>
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            {product.tags.map((tag) => (
                                                <Badge key={tag} variant="secondary" className="bg-background text-foreground">
                                                    {tag}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>

                                    <Button className="mt-4 w-full" variant="secondary">
                                        Add to Cart
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </main>
            </div>
        </div>
        </PublicLayout>
    )
}
