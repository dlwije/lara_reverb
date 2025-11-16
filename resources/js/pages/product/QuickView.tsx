"use client"

import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Edit, X } from "lucide-react"

interface Product {
    id: number
    name: string
    code: string
    type: string
    photo?: string | null
    brand?: { name: string }
    category?: { name: string }
    supplier?: { company?: string; name?: string }
    unit?: { name: string }
    cost: number
    price: number
    rack_location?: string
    taxes?: Array<{ name: string }>
    dont_track_stock: boolean
    deleted_at?: string
}

interface QuickViewProps {
    current: Product | null
    onClose: () => void
    editRow: (product: Product) => void
}

export function QuickView({ current, onClose, editRow }: QuickViewProps) {
    if (!current) return null

    return (
        <div className="p-6">
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl font-semibold">Product Details</h2>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => editRow(current)}>
                        <Edit className="h-4 w-4 mr-2" />
                        Edit
                    </Button>
                    <Button variant="ghost" size="sm" onClick={onClose}>
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                    <div>
                        <label className="text-sm font-medium text-gray-500">Name</label>
                        <p className="text-lg font-semibold">{current.name}</p>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-500">Code</label>
                        <p>{current.code}</p>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-500">Type</label>
                        <Badge variant="secondary">{current.type}</Badge>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-500">Brand</label>
                        <p>{current.brand?.name || "N/A"}</p>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-500">Category</label>
                        <p>{current.category?.name || "N/A"}</p>
                    </div>
                </div>

                <div className="space-y-4">
                    <div>
                        <label className="text-sm font-medium text-gray-500">Supplier</label>
                        <p>{current.supplier?.company || current.supplier?.name || "N/A"}</p>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-500">Unit</label>
                        <p>{current.unit?.name || "N/A"}</p>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-500">Cost</label>
                        <p className="text-lg font-semibold">${Number(current.cost).toFixed(2)}</p>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-500">Price</label>
                        <p className="text-lg font-semibold">${Number(current.price).toFixed(2)}</p>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-500">Rack Location</label>
                        <p>{current.rack_location || "N/A"}</p>
                    </div>
                </div>
            </div>

            {current.photo && (
                <div className="mt-6">
                    <label className="text-sm font-medium text-gray-500">Photo</label>
                    <div className="mt-2">
                        <img
                            src={current.photo || "/placeholder.svg"}
                            alt={current.name}
                            className="rounded-lg max-w-xs max-h-48 object-cover"
                        />
                    </div>
                </div>
            )}

            {current.taxes && current.taxes.length > 0 && (
                <div className="mt-6">
                    <label className="text-sm font-medium text-gray-500">Taxes</label>
                    <div className="flex flex-wrap gap-2 mt-2">
                        {current.taxes.map((tax, index) => (
                            <Badge key={index} variant="outline">
                                {tax.name}
                            </Badge>
                        ))}
                    </div>
                </div>
            )}
        </div>
    )
}
