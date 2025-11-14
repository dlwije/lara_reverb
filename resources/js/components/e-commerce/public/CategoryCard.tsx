import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

interface Category {
    id: string;
    name: string;
    image: string;
    description: string;
    product_count: string;
    slug: string;
    url: string;
};
interface CategoryCardProps {
    name: string;
    image: string;
    category: Category,
}

export function CategoryCard({ category, name, image }: CategoryCardProps) {
    return (
        <Link href={`/product-categories/${category.slug}`}>
            <div className="flex flex-col items-center gap-3 group cursor-pointer">
                <div className="relative w-32 h-32 md:w-36 md:h-36 rounded-full overflow-hidden bg-muted">
                    <img
                        src={image || "/placeholder.svg"}
                        alt={name}
                        className="w-full h-full object-cover transition-all duration-300 grayscale group-hover:grayscale-0"
                        onError={(e) => {
                            // Fallback if image fails to load
                            e.currentTarget.src = "/placeholder.svg";
                        }}
                    />
                </div>
                <p className="text-sm md:text-base font-medium text-center text-foreground">
                    {name}
                </p>
            </div>
        </Link>
    );
}
