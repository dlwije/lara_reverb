"use client"

import { Minus, Plus } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"

interface QuantitySelectorProps {
    value: number
    onChange: (value: number) => void
}

export default function QuantitySelector({ value, onChange }: QuantitySelectorProps) {
    return (
        <div className="flex items-center gap-2 rounded-lg border border-border bg-card p-1">
            <Button variant="ghost" size="sm" onClick={() => onChange(Math.max(1, value - 1))} className="h-8 w-8">
                <Minus className="h-4 w-4" />
            </Button>
            <Input
                type="number"
                value={value}
                onChange={(e) => onChange(Math.max(1, Number.parseInt(e.target.value) || 1))}
                className="h-8 w-12 border-0 text-center bg-transparent"
                min="1"
            />
            <Button variant="ghost" size="sm" onClick={() => onChange(value + 1)} className="h-8 w-8">
                <Plus className="h-4 w-4" />
            </Button>
        </div>
    )
}
