import { CategoryCard } from './CategoryCard'

const categories = [
    {
        name: 'Luxury',
        image: '/storage/images/luxury-handbag.png',
    },
    {
        name: 'Sneakers',
        image: '/storage/images/sneakers-shoes.jpg',
    },
    {
        name: 'P&A',
        image: '/storage/images/yellow-hoodie-streetwear.jpg',
    },
    {
        name: 'Refurbished',
        image: '/storage/images/modern-smartwatch.png',
    },
    {
        name: 'Trading Cards',
        image: '/storage/images/woman-portrait.png',
    },
    {
        name: 'Pre-loved Luxury',
        image: '/storage/images/classic-business-suit.png',
    },
    {
        name: 'Toys',
        image: '/storage/images/vintage-toy-car.jpg',
    },
]

export function PopularCategories() {
    return (
        <section className="bg-background py-8 sm:py-16 lg:py-24">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8">
                <h2 className="text-3xl md:text-4xl font-bold text-center mb-12 text-foreground">
                    Explore Popular Categories
                </h2>

                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-6 md:gap-8">
                    {categories.map((category) => (
                        <CategoryCard
                            key={category.name}
                            name={category.name}
                            image={category.image}
                        />
                    ))}
                </div>
            </div>
        </section>
    )
}
