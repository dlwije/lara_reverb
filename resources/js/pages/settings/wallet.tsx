import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Head } from '@inertiajs/react';
import HeadingSmall from '@/components/heading-small';
import { Building2, CreditCard } from 'lucide-react';
import { WalletBalanceCard } from '@/components/wallet/wallet-balance-card';
import { AddCardPromo } from '@/components/wallet/add-card-promo';
import { EmptyState } from '@/components/wallet/empty-state';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cards & Accounts',
        href: '/wallets',
    },
];

export default function Wallet() {
    const handleAddCard = () => {
        // Add your card addition logic here
        console.log("Add card clicked")
        window.location.href = "/settings/add-card"
    }

    const handleAddAccount = () => {
        // Add your account addition logic here
        console.log("Add account clicked")
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cards & Accounts" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Cards & Accounts" description="Manage your cards and accounts" />

                    {/* Wallet Balance Section */}
                    <WalletBalanceCard balance="16.60" currency="AED" />

                    {/* Add Card Promo */}
                    <AddCardPromo />

                    {/* Cards Section */}
                    <div className="space-y-4">
                        <h2 className="text-xl font-semibold text-foreground">Cards</h2>
                        <EmptyState
                            icon={CreditCard}
                            title="No cards added"
                            description="Add card to enjoy a seamless payments experience"
                            buttonText="Add a card"
                            onButtonClick={handleAddCard}
                        />
                    </div>

                    {/* Bank Accounts Section */}
                    <div className="space-y-4">
                        <h2 className="text-xl font-semibold text-foreground">Bank accounts</h2>
                        <EmptyState
                            icon={Building2}
                            title="No accounts added"
                            description="Add account to enjoy a seamless payments experience"
                            buttonText="Add an account"
                            onButtonClick={handleAddAccount}
                        />
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    )
}
