"use client"

import { useState, useEffect, useRef } from "react"
import { debounce } from "lodash"
import { Input } from "@/components/ui/input"

interface Product {
    id: number
    code: string
    name: string
    quantity?: number
    selected_store?: Array<{ pivot: { quantity: number } }>
}

interface SearchProductProps {
    onProductSelect: (product: Product) => void
    placeholder?: string
    className?: string
}

export default function SearchProduct({
                                          onProductSelect,
                                          placeholder = "Scan barcode or type to search",
                                          className = "",
                                      }: SearchProductProps) {
    const [search, setSearch] = useState("")
    const [result, setResult] = useState<Product[]>([])
    const [loading, setLoading] = useState(false)

    // Mock search function - replace with actual API call
    const searchProduct = async (query: string) => {
        if (!query.trim()) {
            setResult([])
            return
        }

        setLoading(true)

        // Mock API delay
        await new Promise((resolve) => setTimeout(resolve, 300))

        // Mock search results
        const mockProducts: Product[] = [
            {
                id: 1,
                code: "IPH15P",
                name: "iPhone 15 Pro",
                selected_store: [{ pivot: { quantity: 50 } }],
            },
            {
                id: 2,
                code: "SGS24",
                name: "Samsung Galaxy S24",
                selected_store: [{ pivot: { quantity: 30 } }],
            },
            {
                id: 3,
                code: "MBP16",
                name: 'MacBook Pro 16"',
                selected_store: [{ pivot: { quantity: 15 } }],
            },
        ].filter(
            (product) =>
                product.name.toLowerCase().includes(query.toLowerCase()) ||
                product.code.toLowerCase().includes(query.toLowerCase()),
        )

        setResult(mockProducts)
        setLoading(false)
    }

    // Debounced search
    const debouncedSearch = useRef(debounce((query: string) => searchProduct(query), 500)).current

    useEffect(() => {
        debouncedSearch(search)
        return () => {
            debouncedSearch.cancel()
        }
    }, [search, debouncedSearch])

    const handleProductSelect = (product: Product) => {
        onProductSelect(product)
        setSearch("")
        setResult([])
    }

    return (
        <div className={`relative ${className}`}>
            <Input
                id="product-search"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder={placeholder}
                className="w-full"
            />

            {search && result.length > 0 && (
                <div className="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto">
                    {result.map((product) => (
                        <button
                            key={product.id}
                            type="button"
                            onClick={() => handleProductSelect(product)}
                            className="w-full px-4 py-2 text-left hover:bg-gray-50 focus:bg-gray-50 focus:outline-none border-b border-gray-100 last:border-b-0"
                        >
                            <div className="flex justify-between items-center">
                                <div>
                                    <div className="font-medium text-gray-900">{product.name}</div>
                                    <div className="text-sm text-gray-500">{product.code}</div>
                                </div>
                                <div className="text-sm text-gray-400">Stock: {product.selected_store?.[0]?.pivot?.quantity || 0}</div>
                            </div>
                        </button>
                    ))}
                </div>
            )}

            {search && loading && (
                <div className="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg p-4 text-center text-gray-500">
                    Searching...
                </div>
            )}

            {search && !loading && result.length === 0 && (
                <div className="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg p-4 text-center text-gray-500">
                    No products found
                </div>
            )}
        </div>
    )
}
