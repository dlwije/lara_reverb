"use client"

import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import type { LucideIcon } from "lucide-react"

interface EmptyStateProps {
    icon: LucideIcon
    title: string
    description: string
    buttonText: string
    onButtonClick?: () => void
}

export function EmptyState({ icon: Icon, title, description, buttonText, onButtonClick }: EmptyStateProps) {
    return (
        <div className="space-y-4">
            <Card>
                <CardContent className="p-6 flex items-center gap-4">
                    <div className="w-12 h-12 bg-muted rounded-lg flex items-center justify-center">
                        <Icon className="h-6 w-6 text-muted-foreground" />
                    </div>
                    <div className="flex-1">
                        <h3 className="font-medium text-foreground mb-1">{title}</h3>
                        <p className="text-sm text-muted-foreground">{description}</p>
                    </div>
                </CardContent>
            </Card>
            <Button variant="outline" className="w-full bg-transparent" onClick={onButtonClick}>
                {buttonText}
            </Button>
        </div>
    )
}
