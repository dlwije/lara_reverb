import { CategoryCard } from './CategoryCard'
import { useEffect, useState } from 'react';
import apiClient from '@/lib/apiClient';
import { Skeleton } from '@/components/ui/skeleton';


export function PopularCategories() {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchPopularCategories = async () => {
            try {
                setLoading(true);
                const response = await apiClient.get(`/api/v1/slider-categories/popular`);
                // console.log('API Response:', response.data);

                // The API returns data in response.data.data array
                if (response.data.status && response.data.data) {
                    setCategories(response.data.data);
                } else {
                    setCategories([]);
                }
            } catch (err) {
                setError(err.message);
                console.error('Error fetching categories:', err);
            } finally {
                setLoading(false);
            }
        };

        fetchPopularCategories();
    }, []);

    // Show loading state
    if (loading) {
        return (
            <section className="bg-background py-6 sm:py-8 lg:py-16">
                <div className="mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8">
                    {/* Header */}
                    <div className="text-center space-y-4">
                        <Skeleton className="h-12 w-96 mx-auto rounded-xl" />
                        <Skeleton className="h-6 w-64 mx-auto rounded-lg" />
                    </div>

                    {/* Categories Grid */}
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-6 md:gap-8">
                        {Array.from({ length: 7 }).map((_, index) => (
                            <div key={index} className="flex flex-col items-center gap-3 group">
                                {/* Circular Image Container */}
                                <div className="relative w-32 h-32 md:w-36 md:h-36 rounded-full overflow-hidden bg-muted">
                                    <Skeleton className="w-full h-full rounded-full" />
                                </div>
                                {/* Category Name */}
                                <Skeleton className="h-4 w-24 rounded-full" />
                            </div>
                        ))}
                    </div>
                </div>
            </section>
        );
    }

    // Show error state
    if (error) {
        return (
            <section className="bg-background py-8 sm:py-16 lg:py-24">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 className="text-3xl md:text-4xl font-bold text-center mb-12 text-foreground">
                        Explore Popular Categories
                    </h2>
                    <div className="flex justify-center">
                        <p className="text-red-500">Error: {error}</p>
                    </div>
                </div>
            </section>
        );
    }

    // Show empty state
    if (categories.length === 0) {
        return (
            <section className="bg-background py-8 sm:py-16 lg:py-24">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 className="text-3xl md:text-4xl font-bold text-center mb-12 text-foreground">
                        Explore Popular Categories
                    </h2>
                    <div className="flex justify-center">
                        <p>No categories found.</p>
                    </div>
                </div>
            </section>
        );
    }

    return (
        <section className="bg-background py-8 sm:py-16 lg:py-24">
            <div className="mx-auto max-w-7xl space-y-12 px-4 sm:space-y-16 sm:px-6 lg:space-y-24 lg:px-8">
                <h2 className="text-3xl md:text-4xl font-bold text-center mb-12 text-foreground">
                    Explore Popular Categories
                </h2>

                <div className="flex justify-center">
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-6 md:gap-8">
                        {categories.map((category) => (
                            <CategoryCard
                                category={category}
                                key={category.id}
                                name={category.name}
                                image={category.image}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
