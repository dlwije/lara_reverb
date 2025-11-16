import { Head, usePage } from '@inertiajs/react';
import PublicLayout from '@/pages/e-commerce/public/layout';
import Hero from '@/components/e-commerce/public/hero';
import LatestProducts from '@/components/e-commerce/public/latestProducts';
import BestSelling from '@/components/e-commerce/public/bestSelling';
import OurSpecs from '@/components/e-commerce/public/ourSpecs';
import Newsletter from '@/components/e-commerce/public/newsletter';
import { PopularCategories } from '@/components/e-commerce/public/PopularCategories';

export default function Welcome({
                                    canRegister = true,
                                }) {
    // const { auth } = usePage().props;

    return (
        <>
            <Head title="Home">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <PublicLayout>
                <Hero />
                <PopularCategories />
                <LatestProducts />
                <BestSelling />
                <OurSpecs />
                <Newsletter />
            </PublicLayout>

        </>
    );
}
