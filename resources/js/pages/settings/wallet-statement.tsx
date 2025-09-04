import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { ArrowLeft, Menu, Coins } from "lucide-react"
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Wallet Statement',
        href: '/wallet/statement',
    },
];

// Mock transaction data
const transactions = [
    {
        date: "Saturday, 06 April",
        balance: "AED 16.6",
        transactions: [
            {
                id: 1,
                description: "Cashback from ride",
                amount: "+ AED 1.65",
                type: "cashback",
            },
        ],
    },
    {
        date: "Tuesday, 05 March",
        balance: "AED 14.95",
        transactions: [
            {
                id: 2,
                description: "Cashback from ride",
                amount: "+ AED 2.85",
                type: "cashback",
            },
            {
                id: 3,
                description: "Cashback from ride",
                amount: "+ AED 2.1",
                type: "cashback",
            },
        ],
    },
    {
        date: "Thursday, 22 February",
        balance: "AED 10",
        transactions: [
            {
                id: 4,
                description: "Cashback from ride",
                amount: "+ AED 4.25",
                type: "cashback",
            },
        ],
    },
    {
        date: "Monday, 22 January",
        balance: "AED 5.75",
        transactions: [
            {
                id: 5,
                description: "Cashback from ride",
                amount: "+ AED 3.50",
                type: "cashback",
            },
        ],
    },
]

const filterTabs = ["All", "Money In", "Money Out", "Rewards"]

export default function WalletStatementPage() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <SettingsLayout>
                <div className="space-y-6">
                    <div className="min-h-screen bg-background">
                        {/* Header Section */}
                        <div className="bg-gradient-to-br from-slate-800 to-slate-900 text-white p-6 rounded-b-3xl">
                            <div className="flex items-center justify-between mb-6">
                                <Button variant="ghost" size="icon" className="bg-white/20 hover:bg-white/30 text-white cursor-pointer" onClick={() => window.history.back()}>
                                    <ArrowLeft className="h-5 w-5" />
                                </Button>
                                <Button variant="ghost" size="icon" className="bg-white/20 hover:bg-white/30 text-white">
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </div>

                            <div className="text-center">
                                <p className="text-white/80 text-sm mb-2">Wallet balance</p>
                                <h1 className="text-4xl font-bold mb-4">AED 16.60</h1>
                            </div>
                        </div>

                        {/* Filter Tabs */}
                        <div className="px-6 -mt-6 relative z-10">
                            <Card className="bg-background shadow-lg">
                                <CardContent className="p-4">
                                    <div className="flex gap-2">
                                        {filterTabs.map((tab, index) => (
                                            <Button
                                                key={tab}
                                                variant={index === 0 ? "default" : "ghost"}
                                                size="sm"
                                                className={index === 0 ? "bg-slate-800 hover:bg-slate-700 text-white" : ""}
                                            >
                                                {tab}
                                            </Button>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Transactions List */}
                        <div className="px-6 mt-6 space-y-6">
                            {transactions.map((group, groupIndex) => (
                                <div key={groupIndex}>
                                    {/* Date Header */}
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="text-lg font-semibold text-foreground">{group.date}</h3>
                                        <span className="text-sm text-muted-foreground">{group.balance}</span>
                                    </div>

                                    {/* Transaction Items */}
                                    <div className="space-y-3">
                                        {group.transactions.map((transaction) => (
                                            <Card key={transaction.id} className="border-0 shadow-sm">
                                                <CardContent className="p-4">
                                                    <div className="flex items-center gap-4">
                                                        {/* Icon */}
                                                        <div className="w-12 h-12 bg-orange-100 dark:bg-orange-900/20 rounded-full flex items-center justify-center relative">
                                                            <Coins className="h-6 w-6 text-orange-600 dark:text-orange-400" />
                                                            <div className="absolute -top-1 -right-1 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                                                <div className="w-2 h-2 bg-white rounded-full" />
                                                            </div>
                                                        </div>

                                                        {/* Transaction Details */}
                                                        <div className="flex-1">
                                                            <p className="font-medium text-foreground">{transaction.description}</p>
                                                        </div>

                                                        {/* Amount */}
                                                        <div className="text-right">
                                                            <p className="font-semibold text-green-600 dark:text-green-400">{transaction.amount}</p>
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    )
}
