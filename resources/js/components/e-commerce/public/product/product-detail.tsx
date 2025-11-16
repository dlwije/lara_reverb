"use client"

import { useState } from "react"
import { Heart } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { Label } from "@/components/ui/label"
import StarRating from "./star-rating"
import ImageCarousel from "./image-carousel"
import QuantitySelector from "./quantity-selector"
import { useCart } from '@/contexts/CartContext';

export default function ProductDetail({ single_product }) {
    const [selectedColor, setSelectedColor] = useState("black")
    const [selectedSize, setSelectedSize] = useState("L")
    const [selectedDelivery, setSelectedDelivery] = useState("shipping")
    const [selectedWarranty, setSelectedWarranty] = useState(null)
    const [quantity, setQuantity] = useState(1)
    const [isWishlisted, setIsWishlisted] = useState(false)

    const { addToCart, cart } = useCart();
    const [loading, setLoading] = useState(false);
    const [addedToCart, setAddedToCart] = useState(false);

    // Use actual product data from backend
    const product = single_product || {}
    console.log(product)

    // Helper functions to extract data from backend response
    const getProductPrice = () => {
        // Assuming you have a price field in your product model
        return product.price || product.unitPrices?.[0]?.price || 0
    }

    const getOriginalPrice = () => {
        // You might have a original_price or compare_at_price field
        return product.original_price || product.compare_at_price || getProductPrice() * 1.5
    }

    const getDiscount = () => {
        const currentPrice = getProductPrice()
        const originalPrice = getOriginalPrice()
        if (originalPrice > currentPrice) {
            return Math.round(((originalPrice - currentPrice) / originalPrice) * 100)
        }
        return 0
    }

    const getProductImages = () => {
        // Handle different possible image data structures from backend
        if (product.images && Array.isArray(product.images)) {
            return product.images
        }
        if (product.media && Array.isArray(product.media)) {
            return product.media
        }
        if (product.image) {
            return [product.image] // Single image
        }
        if (product.photo) {
            return [product.photo] // Single image
        }
        if (product.featured_image) {
            return [product.featured_image]
        }
        return [] // Return empty array if no images
    }

    const getProductColors = () => {
        // Extract from variations or product attributes
        if (product.variations && product.variations.length > 0) {
            return product.variations.map(variation => ({
                name: variation.color || variation.name,
                value: variation.color?.toLowerCase() || variation.id,
                hex: getColorHex(variation.color)
            }))
        }
        return [
            { name: "Default", value: "default", hex: "#1a1a1a" }
        ]
    }

    const getProductSizes = () => {
        // Extract from variations, unit, or product attributes
        if (product.variations && product.variations.length > 0) {
            return [...new Set(product.variations.map(v => v.size))].filter(Boolean)
        }
        if (product.unit?.subunits) {
            return product.unit.subunits.map(subunit => subunit.name)
        }
        return ["S", "M", "L"] // fallback
    }

    const getColorHex = (colorName) => {
        const colorMap = {
            black: "#1a1a1a",
            green: "#6b9e7f",
            blue: "#3b82f6",
            red: "#ef4444",
            white: "#ffffff",
            gray: "#6b7280"
        }
        return colorMap[colorName?.toLowerCase()] || "#1a1a1a"
    }

    // Delivery options (you might want to make this dynamic based on product/shipping rules)
    const deliveryOptions = [
        { id: "shipping", label: "Shipping - $19", date: "Arrives in 3-5 days", price: 19 },
        { id: "flowbox", label: "Pickup from Flowbox - $9", note: "Pick a Flowbox near you", price: 9 },
        { id: "store", label: "Pickup from our store", note: "Not Available", available: false },
    ]

    // Warranty options (you might want to fetch these from backend)
    const warranties = [
        { id: "1year", label: "1 year", price: 39 },
        { id: "2year", label: "2 year", price: 45 },
        { id: "3year", label: "3 year", price: 69 },
    ]

    if (!single_product) {
        return <div>Loading...</div>
    }
    const { id, name, slug, code, price, cost, description, stocks, supplier, category, on_sale, active, photo } = single_product;

    // Calculate stock quantity
    const stockQuantity = stocks?.[0]?.balance || 0;

    // Check if product is in stock
    const inStock = stockQuantity > 0;

    // Check if product is on sale (you might want to add logic based on cost vs price)
    const isOnSale = cost && parseFloat(cost) > parseFloat(price);

    // Check if this product is already in cart
    const isInCart = cart.content.some((item) => item.id === id);

    const handleAddToCart = async (e) => {
        setQuantity(e.taget.value);

        if (!inStock) return;

        setLoading(true);
        try {
            const success = await addToCart({
                id,
                name,
                qty: quantity,
                price,
                options: { // Pass as object, not string because backend expect a options array
                    category: category?.name,
                    supplier: supplier?.name,
                    // Add any other options as key-value pairs
                }
            });

            if (success) {
                setAddedToCart(true);
                setTimeout(() => setAddedToCart(false), 2000);
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="w-full">
            {/* Main Content */}
            <div className="mx-auto max-w-7xl px-4 py-8">
                <div className="grid gap-8 lg:grid-cols-2">
                    {/* Image Section */}
                    <div className="flex flex-col gap-4">
                        <ImageCarousel images={getProductImages()} />
                    </div>

                    {/* Details Section */}
                    <div className="space-y-6">
                        {/* Title and Rating */}
                        <div>
                            <h1 className="text-3xl font-bold text-foreground">{product.name}</h1>
                            <div className="mt-4 flex items-center gap-3">
                                <StarRating rating={product.rating || 0} />
                                <span className="text-sm text-muted-foreground">
                                    {product.reviews_count || 0} Reviews
                                </span>
                            </div>
                        </div>

                        {/* Pricing */}
                        <div className="space-y-2">
                            <div className="flex items-end gap-3">
                                <span className="text-3xl font-bold text-foreground">
                                    ${getProductPrice()}
                                </span>
                                {getDiscount() > 0 && (
                                    <>
                                        <span className="text-lg text-muted-foreground line-through">
                                            ${getOriginalPrice().toFixed(2)}
                                        </span>
                                        <span className="text-lg font-semibold text-red-500">
                                            ( {getDiscount()}% OFF )
                                        </span>
                                    </>
                                )}
                            </div>
                            <p className="text-sm font-medium text-green-600 dark:text-green-400">
                                Inclusive of all taxes
                            </p>
                        </div>

                        {/* Description */}
                        <p className="text-sm leading-relaxed text-muted-foreground">
                            {product.description || "No description available."}
                        </p>

                        {/* Color Selection - Only show if we have colors */}
                        {getProductColors().length > 0 && (
                            <div className="space-y-3">
                                <h3 className="font-semibold text-foreground">Color:</h3>
                                <div className="flex gap-4">
                                    {getProductColors().map((color) => (
                                        <button
                                            key={color.value}
                                            onClick={() => setSelectedColor(color.value)}
                                            className={`flex h-16 w-16 items-center justify-center rounded-lg border-2 transition-all ${
                                                selectedColor === color.value ? "border-primary" : "border-border hover:border-muted-foreground"
                                            }`}
                                        >
                                            <div
                                                className="h-12 w-12 rounded-lg"
                                                style={{ backgroundColor: color.hex }}
                                                title={color.name}
                                            />
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Size Selection - Only show if we have sizes */}
                        {getProductSizes().length > 0 && (
                            <div className="space-y-3">
                                <h3 className="font-semibold text-foreground">Size:</h3>
                                <div className="flex gap-3">
                                    {getProductSizes().map((size) => (
                                        <button
                                            key={size}
                                            onClick={() => setSelectedSize(size)}
                                            className={`px-6 py-2 rounded-lg font-medium transition-all ${
                                                selectedSize === size
                                                    ? "bg-primary text-primary-foreground"
                                                    : "border border-border bg-card text-foreground hover:border-primary"
                                            }`}
                                        >
                                            {size}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Quantity and CTA */}
                        <div className="flex gap-3">
                            <QuantitySelector value={quantity} onChange={handleAddToCart} />
                            <Button size="lg" className="flex-1 bg-foreground text-background hover:bg-muted-foreground">
                                Buy Now
                            </Button>
                            <Button size="lg" variant="outline" onClick={() => setIsWishlisted(!isWishlisted)}>
                                <Heart className={`h-5 w-5 ${isWishlisted ? "fill-current text-red-500" : ""}`} />
                            </Button>
                        </div>

                        {/* Delivery Options */}
                        <Card className="border-border bg-card p-4">
                            <h3 className="mb-4 font-semibold text-foreground">Delivery Options:</h3>
                            <RadioGroup value={selectedDelivery} onValueChange={setSelectedDelivery}>
                                <div className="space-y-3">
                                    {deliveryOptions.map((option) => (
                                        <div
                                            key={option.id}
                                            className={`flex items-start gap-3 rounded-lg p-3 transition-all ${
                                                !option.available ? "opacity-50" : ""
                                            }`}
                                        >
                                            <RadioGroupItem value={option.id} id={option.id} disabled={!option.available} />
                                            <Label htmlFor={option.id} className="flex-1 cursor-pointer">
                                                <div className="font-medium text-foreground">{option.label}</div>
                                                {option.date && <div className="text-sm text-muted-foreground">{option.date}</div>}
                                                {option.note && (
                                                    <div
                                                        className={`text-sm ${option.available ? "text-blue-600 dark:text-blue-400" : "text-muted-foreground"}`}
                                                    >
                                                        {option.note}
                                                    </div>
                                                )}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                            </RadioGroup>
                        </Card>

                        {/* Extra Warranty */}
                        <Card className="border-border bg-card p-4">
                            <h3 className="mb-4 font-semibold text-foreground">Add Extra Warranty:</h3>
                            <div className="grid gap-3 grid-cols-3">
                                {warranties.map((warranty) => (
                                    <button
                                        key={warranty.id}
                                        onClick={() => setSelectedWarranty(warranty.id)}
                                        className={`rounded-lg p-3 text-center transition-all border ${
                                            selectedWarranty === warranty.id
                                                ? "border-primary bg-primary/10"
                                                : "border-border hover:border-primary"
                                        }`}
                                    >
                                        <div className="font-medium text-foreground">{warranty.label}</div>
                                        <div className="text-sm text-muted-foreground">${warranty.price}</div>
                                    </button>
                                ))}
                            </div>
                        </Card>
                    </div>
                </div>

                {/* Product Accordion Sections */}
                <Card className="mt-8 border-border bg-card p-6">
                    <Accordion type="single" collapsible className="w-full">
                        <AccordionItem value="details">
                            <AccordionTrigger className="text-lg font-semibold text-foreground">
                                Product Details
                            </AccordionTrigger>
                            <AccordionContent className="text-muted-foreground">
                                {product.long_description || product.details || "No detailed description available."}
                            </AccordionContent>
                        </AccordionItem>

                        <AccordionItem value="specs">
                            <AccordionTrigger className="text-lg font-semibold text-foreground">
                                Specifications
                            </AccordionTrigger>
                            <AccordionContent className="space-y-2 text-muted-foreground">
                                {/* Add actual product specifications here */}
                                {product.brand && <div>Brand: {product.brand.name}</div>}
                                {product.category && <div>Category: {product.category.name}</div>}
                                {product.unit && <div>Unit: {product.unit.name}</div>}
                                {/* Add more specifications as needed */}
                            </AccordionContent>
                        </AccordionItem>

                        <AccordionItem value="warranty">
                            <AccordionTrigger className="text-lg font-semibold text-foreground">
                                Warranty
                            </AccordionTrigger>
                            <AccordionContent className="text-muted-foreground">
                                {product.warranty_info || "All products come with a standard 1-year manufacturer's warranty covering defects in materials and workmanship. Additional warranty options are available for extended coverage."}
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </Card>
            </div>
        </div>
    )
}
