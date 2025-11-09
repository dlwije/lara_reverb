import { dashboard, login, register } from '@/routes';

import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '@/pages/e-commerce/public/layout';
import Hero from '@/components/e-commerce/public/hero';
import LatestProducts from '@/components/e-commerce/public/latestProducts';
import BestSelling from '@/components/e-commerce/public/bestSelling';
import OurSpecs from '@/components/e-commerce/public/ourSpecs';
import Newsletter from '@/components/e-commerce/public/newsletter';

export default function Welcome({
                                    canRegister = true,
                                }) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <PublicLayout>
                <Hero />
                <LatestProducts />
                <BestSelling />
                <OurSpecs />
                <Newsletter />
            </PublicLayout>

        </>
    );
}
