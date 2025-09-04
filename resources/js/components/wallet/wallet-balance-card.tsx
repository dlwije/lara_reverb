import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { ArrowRight } from "lucide-react"
import { Link } from '@inertiajs/react';

interface WalletBalanceCardProps {
    balance: string
    currency: string
}

export function WalletBalanceCard({ balance, currency }: WalletBalanceCardProps) {
    return (
        <Card className="bg-slate-900 text-white border-0">
            <CardContent className="p-6 text-center">
                <p className="text-sm text-slate-300 mb-2">Wallet balance</p>
                <h2 className="text-3xl font-bold mb-4">
                    {currency} {balance}
                </h2>
                <Link href="/wallet/statement">
                    <Button variant="link" className="text-slate-300 hover:text-white p-0 h-auto mb-6 cursor-pointer">
                        View wallet statement <ArrowRight className="ml-1 h-4 w-4" />
                    </Button>
                </Link>
                <div className="flex gap-3">
                    <Button className="flex-1 bg-white text-slate-900 hover:bg-slate-100">Add funds</Button>
                    <Button className="flex-1 bg-white text-slate-900 hover:bg-slate-100">Withdraw to bank</Button>
                </div>
            </CardContent>
        </Card>
    )
}
