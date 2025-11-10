"use client"

import { useState } from "react"
import { ChevronLeft, ChevronRight } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"

export default function ImageCarousel({ images = [] }) {
    const [currentSlide, setCurrentSlide] = useState(0)

    // Use actual images from backend or fallback to placeholder
    const slides = images.length > 0 ? images : ["/placeholder.svg"]

    const nextSlide = () => {
        setCurrentSlide((prev) => (prev + 1) % slides.length)
    }

    const prevSlide = () => {
        setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length)
    }

    // Helper function to get image URL
    const getImageUrl = (image) => {
        if (typeof image === 'string') return image
        if (image.url) return image.url
        if (image.path) return image.path
        if (image.original_url) return image.original_url
        return "/placeholder.svg"
    }

    // Helper function to get image alt text
    const getImageAlt = (image, index) => {
        if (typeof image === 'object') {
            return image.alt || image.caption || `Product image ${index + 1}`
        }
        return `Product image ${index + 1}`
    }

    return (
        <>
            <Card className="relative overflow-hidden bg-muted">
                <div className="aspect-square w-full bg-gradient-to-b from-muted-foreground/5 to-muted-foreground/10 flex items-center justify-center">
                    <img
                        src={getImageUrl(slides[currentSlide])}
                        alt={getImageAlt(slides[currentSlide], currentSlide)}
                        className="h-full w-full object-cover"
                        onError={(e) => {
                            e.target.src = "/placeholder.svg"
                        }}
                    />
                </div>

                {/* Navigation Buttons - Only show if multiple images */}
                {slides.length > 1 && (
                    <div className="absolute inset-0 flex items-center justify-between px-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={prevSlide}
                            className="h-10 w-10 rounded-full bg-background/80 hover:bg-background"
                        >
                            <ChevronLeft className="h-6 w-6" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={nextSlide}
                            className="h-10 w-10 rounded-full bg-background/80 hover:bg-background"
                        >
                            <ChevronRight className="h-6 w-6" />
                        </Button>
                    </div>
                )}

                {/* Dot Indicators - Only show if multiple images */}
                {slides.length > 1 && (
                    <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                        {slides.map((_, index) => (
                            <button
                                key={index}
                                onClick={() => setCurrentSlide(index)}
                                className={`h-2 w-2 rounded-full transition-all ${
                                    index === currentSlide ? "bg-foreground w-6" : "bg-foreground/40 hover:bg-foreground/60"
                                }`}
                            />
                        ))}
                    </div>
                )}

                {/* Thumbnail Gallery - Optional: Add if you want thumbnails */}
                {slides.length > 1 && (
                    <div className="mt-4 flex gap-2 overflow-x-auto pb-2">
                        {slides.map((image, index) => (
                            <button
                                key={index}
                                onClick={() => setCurrentSlide(index)}
                                className={`flex-shrink-0 overflow-hidden rounded-lg border-2 transition-all ${
                                    index === currentSlide ? "border-primary" : "border-transparent hover:border-muted-foreground"
                                }`}
                            >
                                <img
                                    src={getImageUrl(image)}
                                    alt={getImageAlt(image, index)}
                                    className="h-16 w-16 object-cover"
                                    onError={(e) => {
                                        e.target.src = "/placeholder.svg"
                                    }}
                                />
                            </button>
                        ))}
                    </div>
                )}
            </Card>
        </>
    )
}
