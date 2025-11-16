import { Card, CardContent } from "@/components/ui/card"
import { CreditCard } from "lucide-react"

export function AddCardPromo() {
    return (
        <Card className="bg-gradient-to-r from-green-50 to-emerald-50 border-green-200">
            <CardContent className="p-6 flex items-center gap-4">
                <div className="flex-1">
                    <h3 className="font-semibold text-slate-900 mb-1">Add your debit card</h3>
                    <p className="font-medium text-slate-900 mb-2">for free money transfers</p>
                    <p className="text-sm text-slate-600">Your card info is encrypted and secure.</p>
                </div>
                <div className="relative">
                    <div className="w-20 h-12 bg-green-500 rounded-lg flex items-center justify-center transform rotate-12">
                        <CreditCard className="h-6 w-6 text-white" />
                    </div>
                    <div className="absolute -top-1 -right-1 w-16 h-10 bg-green-600 rounded-lg flex items-center justify-center">
                        <span className="text-xs font-bold text-white">VISA</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    )
}
