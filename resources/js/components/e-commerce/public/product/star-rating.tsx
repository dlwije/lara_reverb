import { Star } from "lucide-react"

interface StarRatingProps {
    rating: number
    maxRating?: number
}

export default function StarRating({ rating, maxRating = 5 }: StarRatingProps) {
    return (
        <div className="flex items-center gap-1">
            {Array.from({ length: maxRating }).map((_, i) => (
                <Star
                    key={i}
                    className={`h-5 w-5 ${
                        i < Math.floor(rating)
                            ? "fill-amber-400 text-amber-400"
                            : i < rating
                                ? "fill-amber-400 text-amber-400 opacity-50"
                                : "text-muted-foreground"
                    }`}
                />
            ))}
        </div>
    )
}
