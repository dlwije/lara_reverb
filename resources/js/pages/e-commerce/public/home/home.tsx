// pages/Home/Index.jsx

import { HeroBanner } from '@/components/e-commerce/public/home/HeroBanner';
import { PopularCategories } from '@/components/e-commerce/public/PopularCategories';
import { FeaturedProducts } from '@/components/e-commerce/public/home/FeaturedProducts';
import { HotDeals } from '@/components/e-commerce/public/home/HotDeals';
import { NewArrivals } from '@/components/e-commerce/public/home/NewArrivals';
import { BrandsShowcase } from '@/components/e-commerce/public/home/BrandsShowcase';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/pages/e-commerce/public/layout';

export default function Homepage() {
    return (
        <>
            <Head title="Welcome to Our Store - Best E-commerce Experience">
                <meta name="description" content="Discover amazing products at great prices. Shop from thousands of items across various categories." />
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>

            <PublicLayout>
                {/* Hero Banner */}
                <HeroBanner />

                {/* Popular Categories */}
                <PopularCategories />

                {/* Featured Products */}
                <FeaturedProducts />

                {/* Hot Deals */}
                <HotDeals />

                {/* New Arrivals */}
                <NewArrivals />

                {/* Brands Showcase */}
                <BrandsShowcase />
            </PublicLayout>
        </>
    )
}
