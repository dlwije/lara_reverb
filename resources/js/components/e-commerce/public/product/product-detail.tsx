"use client"

import { useState, useEffect } from "react"
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
import { formatCurrency } from '@/lib/e-commerce/amountHelper';

export default function ProductDetail({ single_product }) {
    const [selectedColor, setSelectedColor] = useState("")
    const [selectedSize, setSelectedSize] = useState("")
    const [selectedDelivery, setSelectedDelivery] = useState("shipping")
    const [selectedWarranty, setSelectedWarranty] = useState(null)
    const [quantity, setQuantity] = useState(1)
    const [isWishlisted, setIsWishlisted] = useState(false)
    const [selectedVariation, setSelectedVariation] = useState(null)

    const { addToCart, cart } = useCart();
    const [loading, setLoading] = useState(false);
    const [addedToCart, setAddedToCart] = useState(false);

    const product = single_product || {}

    // Extract available colors and sizes from variations
    const availableColors = [...new Set(product.variations?.map(v => v.meta?.Color).filter(Boolean) || [])]
    const availableSizes = [...new Set(product.variations?.map(v => v.meta?.Size).filter(Boolean) || [])]

    // Initialize selected color and size
    useEffect(() => {
        if (availableColors.length > 0 && !selectedColor) {
            setSelectedColor(availableColors[0])
        }
        if (availableSizes.length > 0 && !selectedSize) {
            setSelectedSize(availableSizes[0])
        }
    }, [availableColors, availableSizes, selectedColor, selectedSize])

    // Find the current variation based on selected color and size
    useEffect(() => {
        if (selectedColor && selectedSize && product.variations) {
            const variation = product.variations.find(v =>
                v.meta?.Color === selectedColor && v.meta?.Size === selectedSize
            )
            setSelectedVariation(variation || null)
        }
    }, [selectedColor, selectedSize, product.variations])

    // Helper functions to extract data
    const getProductPrice = () => {
        return selectedVariation?.price || product.price || 0
    }

    const getOriginalPrice = () => {
        return selectedVariation?.cost || product.cost || getProductPrice() * 1.5
    }

    const getDiscount = () => {
        const currentPrice = parseFloat(getProductPrice())
        const originalPrice = parseFloat(getOriginalPrice())
        if (originalPrice > currentPrice) {
            return Math.round(((originalPrice - currentPrice) / originalPrice) * 100)
        }
        return 0
    }

    const getProductImages = () => {
        if (product.photo) {
            return [product.photo]
        }
        return []
    }

    const getColorHex = (colorName) => {
        const colorMap = {
            silver: "#c0c0c0",
            yellow: "#fde047",
            black: "#1a1a1a",
            green: "#6b9e7f",
            blue: "#3b82f6",
            red: "#ef4444",
            white: "#ffffff",
            gray: "#6b7280"
        }
        return colorMap[colorName?.toLowerCase()] || "#1a1a1a"
    }

    // Get stock quantity for current variation
    const getStockQuantity = () => {
        if (selectedVariation?.stocks?.length > 0) {
            return selectedVariation.stocks.reduce((total, stock) => total + (stock.balance || 0), 0)
        }
        return 0
    }

    const stockQuantity = getStockQuantity()
    const inStock = stockQuantity > 0

    // Check if this product is already in cart
    const isInCart = cart.content.some((item) => item.id === product.id)

    const handleQuantityChange = (newQuantity) => {
        setQuantity(newQuantity)
    }

    const handleAddToCart = async () => {
        if (!inStock) return

        setLoading(true)
        try {
            const itemToAdd = {
                id: selectedVariation?.id || product.id,
                name: product.name,
                qty: quantity,
                price: getProductPrice(),
                options: {
                    color: selectedColor,
                    size: selectedSize,
                    category: product.category?.name,
                    supplier: product.supplier?.name,
                    variationCode: selectedVariation?.code
                }
            }

            const success = await addToCart(itemToAdd)

            if (success) {
                setAddedToCart(true)
                setTimeout(() => setAddedToCart(false), 2000)
            }
        } catch (error) {
            console.error('Error adding to cart:', error)
        } finally {
            setLoading(false)
        }
    }

    // Delivery options
    const deliveryOptions = [
        { id: "shipping", label: "Shipping - $19", date: "Arrives in 3-5 days", price: 19 },
        { id: "flowbox", label: "Pickup from Flowbox - $9", note: "Pick a Flowbox near you", price: 9 },
        { id: "store", label: "Pickup from our store", note: "Not Available", available: false },
    ]

    // Warranty options
    const warranties = [
        { id: "1year", label: "1 year", price: 39 },
        { id: "2year", label: "2 year", price: 45 },
        { id: "3year", label: "3 year", price: 69 },
    ]

    if (!single_product) {
        return <div>Loading...</div>
    }

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
                            <div className="mt-2 text-sm text-muted-foreground">
                                Code: {selectedVariation?.code || product.code}
                            </div>
                            <div className="mt-4 flex items-center gap-3">
                                <StarRating rating={product.rating || 0} />
                                <span className="text-sm text-muted-foreground">
                                    {product.reviews_count || 0} Reviews
                                </span>
                            </div>
                        </div>

                        {/* Stock Status */}
                        <div className={`text-sm font-medium ${inStock ? 'text-green-600' : 'text-red-600'}`}>
                            {inStock ? `In Stock (${stockQuantity} available)` : 'Out of Stock'}
                        </div>

                        {/* Pricing */}
                        <div className="space-y-2">
                            <div className="flex items-end gap-3">
                                <span className="text-3xl font-bold text-foreground">
                                    {formatCurrency(getProductPrice())}
                                </span>
                                {getDiscount() > 0 && (
                                    <>
                                        <span className="text-lg text-muted-foreground line-through">
                                            {formatCurrency(getOriginalPrice())}
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
                        {product.description && (
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                {product.description}
                            </p>
                        )}

                        {/* Features */}
                        {product.features && (
                            <div className="space-y-2">
                                <h3 className="font-semibold text-foreground">Features:</h3>
                                <p className="text-sm text-muted-foreground">{product.features}</p>
                            </div>
                        )}

                        {/* Color Selection */}
                        {availableColors.length > 0 && (
                            <div className="space-y-3">
                                <h3 className="font-semibold text-foreground">Color:</h3>
                                <div className="flex gap-4">
                                    {availableColors.map((color) => (
                                        <button
                                            key={color}
                                            onClick={() => setSelectedColor(color)}
                                            className={`flex h-16 w-16 items-center justify-center rounded-lg border-2 transition-all ${
                                                selectedColor === color ? "border-primary" : "border-border hover:border-muted-foreground"
                                            }`}
                                        >
                                            <div
                                                className="h-12 w-12 rounded-lg border border-gray-300"
                                                style={{ backgroundColor: getColorHex(color) }}
                                                title={color}
                                            />
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Size Selection */}
                        {availableSizes.length > 0 && (
                            <div className="space-y-3">
                                <h3 className="font-semibold text-foreground">Size:</h3>
                                <div className="flex gap-3 flex-wrap">
                                    {availableSizes.map((size) => (
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

                        {/* Selected Variation Info */}
                        {selectedVariation && (
                            <div className="p-3 bg-muted rounded-lg">
                                <p className="text-sm text-muted-foreground">
                                    Selected: {selectedColor} - {selectedSize} |
                                    SKU: {selectedVariation.sku} |
                                    Stock: {stockQuantity}
                                </p>
                            </div>
                        )}

                        {/* Quantity and CTA */}
                        <div className="flex gap-3">
                            <QuantitySelector
                                value={quantity}
                                onChange={handleQuantityChange}
                                max={stockQuantity}
                                disabled={!inStock}
                            />
                            <Button
                                size="lg"
                                className="flex-1 bg-foreground text-background hover:bg-muted-foreground"
                                disabled={!inStock || loading}
                            >
                                {loading ? "Adding..." : "Buy Now"}
                            </Button>
                            <Button
                                size="lg"
                                variant="outline"
                                onClick={() => setIsWishlisted(!isWishlisted)}
                                disabled={!inStock}
                            >
                                <Heart className={`h-5 w-5 ${isWishlisted ? "fill-current text-red-500" : ""}`} />
                            </Button>
                        </div>

                        {addedToCart && (
                            <div className="text-green-600 text-sm font-medium">
                                ✓ Added to cart successfully!
                            </div>
                        )}

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
                                {product.details || "No detailed description available."}
                            </AccordionContent>
                        </AccordionItem>

                        <AccordionItem value="specs">
                            <AccordionTrigger className="text-lg font-semibold text-foreground">
                                Specifications
                            </AccordionTrigger>
                            <AccordionContent className="space-y-2 text-muted-foreground">
                                {product.brand && <div><strong>Brand:</strong> {product.brand.name}</div>}
                                {product.category && <div><strong>Category:</strong> {product.category.name}</div>}
                                {product.unit && <div><strong>Unit:</strong> {product.unit.name}</div>}
                                {product.supplier && <div><strong>Supplier:</strong> {product.supplier.name}</div>}
                                {product.code && <div><strong>Product Code:</strong> {product.code}</div>}
                                {selectedVariation?.code && <div><strong>Variation Code:</strong> {selectedVariation.code}</div>}
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
