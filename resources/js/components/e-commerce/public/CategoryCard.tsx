import { cn } from '@/lib/utils';

interface CategoryCardProps {
    name: string
    image: string
}

export function CategoryCard({ name, image }: CategoryCardProps) {
    return (
        <div className="flex flex-col items-center gap-3 group cursor-pointer">
            <div className="relative w-32 h-32 md:w-36 md:h-36 rounded-full overflow-hidden bg-muted">
                <img
                    src={image || "/placeholder.svg"}
                    alt={name}
                    className={cn(
                        'w-full h-full object-cover transition-all duration-300',
                        'grayscale group-hover:grayscale-0'
                    )}
                />
            </div>
            <p className="text-sm md:text-base font-medium text-center text-foreground">
                {name}
            </p>
        </div>
    )
}
