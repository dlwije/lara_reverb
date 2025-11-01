import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { ArrowLeft, Menu, Coins } from "lucide-react"
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import SettingsLayout from '@/layouts/settings/layout';
import { useEffect, useState } from 'react';
import { Transaction, TransactionGroup, WalletFilters } from '@/types/wallet';
import apiClient from '@/lib/apiClient';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Wallet Statement',
        href: '/wallet/statement',
    },
];

const apiEndpoint = "/api/v1/wallet/transactions";

const filterTabs = ["All", "Money In", "Money Out", "Rewards"]
// const filterTabs = ['All', 'Credit', 'Debit', 'This Month', 'Last Month'];

export default function WalletStatementPage() {

    const [transactions, setTransactions] = useState<Transaction[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [filters, setFilters] = useState({
        period: 'all',
        page: 1,
        limit: 20
    });
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        total: 0,
        has_more: false
    });

    const filterTabs = ['All', 'Credit', 'Debit', 'This Month', 'Last Month'];

    const fetchWalletStatement = async (loadMore = false) => {
        try {
            if (!loadMore) {
                setLoading(true);
            }
            setError(null);

            const params = new URLSearchParams({
                period: filters.period,
                page: filters.page.toString(),
                limit: filters.limit.toString()
            });

            const response = await apiClient.post(`${apiEndpoint}?${params}`);

            if (response.status) {
                // Safely handle the transaction data
                const transactionData = Array.isArray(response.data.data.data) ? response.data.data.data : [];

                console.log('transactionData', transactionData);
                // console.log(transactionData);
                if (loadMore) {
                    setTransactions(prev => [...prev, ...transactionData]);
                } else {
                    setTransactions(transactionData);
                }

                setPagination({
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    total: response.data.total,
                    has_more: response.data.next_page_url !== null
                });
            } else {
                setError(response.message || 'Failed to fetch wallet statement');
            }
        } catch (err) {
            setError('An error occurred while fetching wallet data');
            console.error('Error fetching wallet statement:', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchWalletStatement();
    }, [filters.period]);

    const handleFilterChange = (tab: string) => {
        const periodMap: { [key: string]: string } = {
            'All': 'all',
            'Credit': 'credit',
            'Debit': 'debit',
            'This Month': 'current_month',
            'Last Month': 'last_month'
        };

        const typeMap: { [key: string]: string } = {
            'Credit': 'CR',
            'Debit': 'DR'
        };

        setFilters(prev => ({
            ...prev,
            period: periodMap[tab] || 'all',
            type: typeMap[tab],
            page: 1
        }));
    };

    const handleLoadMore = () => {
        const nextPage = pagination.current_page + 1;
        setFilters(prev => ({
            ...prev,
            page: nextPage
        }));
        fetchWalletStatement(true);
    };

    // Format amount display based on direction
    const formatAmountDisplay = (transaction: Transaction) => {
        const isCredit = transaction.direction === 'CR';
        const colorClass = isCredit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';

        return {
            display: transaction.formatted_amount,
            colorClass
        };
    };

    // Get transaction type display name
    const getTransactionType = (paymentType: string) => {
        const typeMap: { [key: string]: string } = {
            'plugins/wallet::wallet.deposit': 'Deposit',
            'plugins/wallet::wallet.withdrawal': 'Withdrawal',
            'plugins/wallet::wallet.gift_card_redemption': 'Gift Card Redemption',
            'plugins/wallet::wallet.payment': 'Payment'
        };

        return typeMap[paymentType] || paymentType;
    };

    // Group transactions by date
    const groupTransactionsByDate = () => {
        // Ensure transactions is always an array
        const safeTransactions = Array.isArray(transactions) ? transactions : [];

        console.log('dsgh');
        console.log(safeTransactions);
        if (safeTransactions.length === 0) {
            return [];
        }

        const groups: { [key: string]: Transaction[] } = {};

        safeTransactions.forEach(transaction => {
            if (!transaction || !transaction.created_at) return;

            try {
                const date = new Date(transaction.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                if (!groups[date]) {
                    groups[date] = [];
                }
                groups[date].push(transaction);
            } catch (err) {
                console.warn('Invalid transaction date:', transaction.created_at);
            }
        });

        return Object.entries(groups).map(([date, transactions]) => ({
            date,
            transactions,
            // Use the running balance from the last transaction of the day
            balance: transactions[transactions.length - 1]?.formatted_running_balance || '0.00 AED'
        }));
    };

    const transactionGroups = groupTransactionsByDate();

    if (loading && transactions.length === 0) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <SettingsLayout>
                    <div className="min-h-screen bg-background flex items-center justify-center">
                        <div className="text-center">
                            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-slate-800 mx-auto"></div>
                            <p className="mt-4 text-muted-foreground">Loading wallet statement...</p>
                        </div>
                    </div>
                </SettingsLayout>
            </AppLayout>
        );
    }

    if (error && transactions.length === 0) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <SettingsLayout>
                    <div className="min-h-screen bg-background flex items-center justify-center">
                        <div className="text-center">
                            <p className="text-red-600 dark:text-red-400 mb-4">{error}</p>
                            <Button onClick={() => fetchWalletStatement()}>Retry</Button>
                        </div>
                    </div>
                </SettingsLayout>
            </AppLayout>
        );
    }

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
                                <h1 className="text-4xl font-bold mb-4">
                                    {/* You might want to get this from a separate balance endpoint */}
                                    {transactions[0]?.formatted_running_balance || '0.00 AED'}
                                </h1>
                            </div>
                        </div>

                        {/* Filter Tabs */}
                        <div className="px-6 -mt-6 relative z-10">
                            <Card className="bg-background shadow-lg">
                                <CardContent className="p-4">
                                    <div className="flex gap-2 overflow-x-auto">
                                        {filterTabs.map((tab) => {
                                            const periodMap: { [key: string]: string } = {
                                                'All': 'all',
                                                'Credit': 'credit',
                                                'Debit': 'debit',
                                                'This Month': 'current_month',
                                                'Last Month': 'last_month'
                                            };
                                            const isActive = filters.period === periodMap[tab];

                                            return (
                                                <Button
                                                    key={tab}
                                                    variant={isActive ? "default" : "ghost"}
                                                    size="sm"
                                                    className={isActive ? "bg-slate-800 hover:bg-slate-700 text-white" : ""}
                                                    onClick={() => handleFilterChange(tab)}
                                                >
                                                    {tab}
                                                </Button>
                                            );
                                        })}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Transactions List */}
                        <div className="px-6 mt-6 space-y-6">
                            {!Array.isArray(transactions) || transactions.length === 0 ? (
                                <div className="text-center py-12">
                                    <Coins className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                                    <p className="text-muted-foreground">No transactions found</p>
                                    <p className="text-sm text-muted-foreground mt-2">
                                        Your transaction history will appear here
                                    </p>
                                </div>
                            ) : (
                                transactionGroups.map((group, groupIndex) => (
                                    <div key={groupIndex}>
                                        {/* Date Header */}
                                        <div className="flex items-center justify-between mb-4">
                                            <h3 className="text-lg font-semibold text-foreground">{group.date}</h3>
                                            <span className="text-sm text-muted-foreground">{group.balance}</span>
                                        </div>

                                        {/* Transaction Items */}
                                        <div className="space-y-3">
                                            {group.transactions.map((transaction) => {
                                                if (!transaction) return null;

                                                const amountInfo = formatAmountDisplay(transaction);
                                                const transactionType = getTransactionType(transaction.payment_type);

                                                return (
                                                    <Card key={transaction.id} className="border-0 shadow-sm">
                                                        <CardContent className="p-4">
                                                            <div className="flex items-center gap-4">
                                                                {/* Icon */}
                                                                <div className="w-12 h-12 bg-orange-100 dark:bg-orange-900/20 rounded-full flex items-center justify-center relative">
                                                                    <Coins className="h-6 w-6 text-orange-600 dark:text-orange-400" />
                                                                    <div className={`absolute -top-1 -right-1 w-5 h-5 ${
                                                                        transaction.direction === 'CR' ? 'bg-green-500' : 'bg-red-500'
                                                                    } rounded-full flex items-center justify-center`}>
                                                                        <div className="w-2 h-2 bg-white rounded-full" />
                                                                    </div>
                                                                </div>

                                                                {/* Transaction Details */}
                                                                <div className="flex-1">
                                                                    <p className="font-medium text-foreground">
                                                                        {transactionType}
                                                                    </p>
                                                                    <p className="text-sm text-muted-foreground mt-1">
                                                                        {new Date(transaction.created_at).toLocaleTimeString('en-US', {
                                                                            hour: '2-digit',
                                                                            minute: '2-digit'
                                                                        })}
                                                                    </p>
                                                                    {transaction.description && (
                                                                        <p className="text-xs text-muted-foreground mt-1">
                                                                            {transaction.description.replace('plugins/wallet::wallet.', '')}
                                                                        </p>
                                                                    )}
                                                                </div>

                                                                {/* Amount and Balance */}
                                                                <div className="text-right">
                                                                    <p className={`font-semibold ${amountInfo.colorClass}`}>
                                                                        {amountInfo.display}
                                                                    </p>
                                                                    <p className="text-sm text-muted-foreground mt-1">
                                                                        Balance: {transaction.formatted_running_balance}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </CardContent>
                                                    </Card>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>

                        {/* Load More Button */}
                        {pagination.has_more && (
                            <div className="px-6 mt-6 text-center">
                                <Button
                                    variant="outline"
                                    onClick={handleLoadMore}
                                    disabled={loading}
                                >
                                    {loading ? 'Loading...' : 'Load More'}
                                </Button>
                            </div>
                        )}

                        {/* Total Count */}
                        {Array.isArray(transactions) && transactions.length > 0 && (
                            <div className="px-6 mt-4 text-center">
                                <p className="text-sm text-muted-foreground">
                                    Showing {transactions.length} of {pagination.total} transactions
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    )
}
