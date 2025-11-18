import * as React from "react"
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
    type CarouselApi,
} from "@/components/ui/carousel"
import { ChevronLeft, ChevronRight } from "lucide-react"

export function TopSmallSlider() {
    const [api, setApi] = React.useState<CarouselApi>()
    const [current, setCurrent] = React.useState(0)

    const promoSlides = [
        { text: "🚚 Free shipping on orders $50+", emoji: "🚚" },
        { text: "⭐ New customer? Get 15% off", emoji: "⭐" },
        { text: "🔔 Sign up for exclusive deals", emoji: "🔔" },
        { text: "💳 Secure payment processing", emoji: "💳" },
    ]

    React.useEffect(() => {
        if (!api) return

        setCurrent(api.selectedScrollSnap())
        api.on("select", () => setCurrent(api.selectedScrollSnap()))
    }, [api])

    // Auto-slide
    React.useEffect(() => {
        if (!api) return
        const interval = setInterval(() => api.scrollNext(), 4000)
        return () => clearInterval(interval)
    }, [api])

    return (
        <div className="w-full bg-background/50 dark:bg-background/30 border-b border-border/50 py-2 relative">
            <Carousel setApi={setApi} opts={{ loop: true }} className="w-full">
                <CarouselContent className="ml-0">
                    {promoSlides.map((slide, index) => (
                        <CarouselItem key={index} className="pl-4 basis-full">
                            <div className="flex items-center justify-center px-4 py-2">
                                <span className="text-lg mr-2">{slide.emoji}</span>
                                <p className="text-sm font-medium text-foreground/90 text-center">
                                    {slide.text}
                                </p>
                            </div>
                        </CarouselItem>
                    ))}
                </CarouselContent>

                {/* Simple & Clean Controls */}
                <div className="absolute inset-y-0 left-2 flex items-center">
                    <CarouselPrevious className="h-6 w-6 bg-background/90 border border-border text-foreground/70 hover:text-foreground hover:bg-accent shadow-sm" />
                </div>
                <div className="absolute inset-y-0 right-2 flex items-center">
                    <CarouselNext className="h-6 w-6 bg-background/90 border border-border text-foreground/70 hover:text-foreground hover:bg-accent shadow-sm" />
                </div>

                {/* Working Dash Indicators */}
                <div className="flex justify-center space-x-1.5 mt-1">
                    {promoSlides.map((_, index) => (
                        <button
                            key={index}
                            onClick={() => api?.scrollTo(index)}
                            className={`h-1 rounded-full transition-all duration-300 ${
                                current === index
                                    ? "bg-foreground w-6"
                                    : "bg-muted-foreground/30 w-3 hover:bg-muted-foreground/50"
                            }`}
                        />
                    ))}
                </div>
            </Carousel>
        </div>
    )
}
