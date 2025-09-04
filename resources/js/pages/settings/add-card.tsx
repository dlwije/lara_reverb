"use client"

import type React from "react"

import { useState } from "react"
import { ArrowLeft, HelpCircle, Shield } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"

export default function AddCardPage() {
    const [formData, setFormData] = useState({
        cardNumber: "",
        expiryDate: "",
        cvv: "",
        nickname: "",
    })
    const [errors, setErrors] = useState<Record<string, string>>({})

    const handleBack = () => {
        window.history.back()
    }

    const handleInputChange = (field: string, value: string) => {
        setFormData((prev) => ({ ...prev, [field]: value }))
        // Clear error when user starts typing
        if (errors[field]) {
            setErrors((prev) => ({ ...prev, [field]: "" }))
        }
    }

    const formatCardNumber = (value: string) => {
        // Remove all non-digits
        const digits = value.replace(/\D/g, "")
        // Add spaces every 4 digits
        return digits.replace(/(\d{4})(?=\d)/g, "$1 ")
    }

    const formatExpiryDate = (value: string) => {
        // Remove all non-digits
        const digits = value.replace(/\D/g, "")
        // Add slash after 2 digits
        if (digits.length >= 2) {
            return digits.slice(0, 2) + "/" + digits.slice(2, 4)
        }
        return digits
    }

    const validateForm = () => {
        const newErrors: Record<string, string> = {}

        if (!formData.cardNumber.replace(/\s/g, "")) {
            newErrors.cardNumber = "Please provide card number"
        }

        if (!formData.expiryDate) {
            newErrors.expiryDate = "Please provide expiry date"
        }

        if (!formData.cvv) {
            newErrors.cvv = "Please provide CVV"
        }

        setErrors(newErrors)
        return Object.keys(newErrors).length === 0
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        if (validateForm()) {
            // Handle form submission
            console.log("Card data:", formData)
        }
    }

    return (
        <div className="min-h-screen bg-background">
            {/* Header */}
            <div className="flex items-center gap-4 p-4 border-b">
                <Button variant="ghost" size="icon" onClick={handleBack} className="h-10 w-10 rounded-lg border bg-muted/50">
                    <ArrowLeft className="h-5 w-5" />
                </Button>
                <h1 className="text-lg font-semibold">Add new card</h1>
            </div>

            <div className="p-4 space-y-6">
                {/* Note */}
                <div className="bg-muted/50 p-4 rounded-lg">
                    <p className="text-sm text-muted-foreground">
                        <span className="font-medium">Note</span> In order to verify your account we may charge your account with
                        small amount that will be refunded.
                    </p>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Card Number */}
                    <div className="space-y-2">
                        <Label htmlFor="cardNumber" className="text-base font-medium">
                            Card number
                        </Label>
                        <Input
                            id="cardNumber"
                            type="text"
                            placeholder="1234 5678 9012 3456"
                            value={formatCardNumber(formData.cardNumber)}
                            onChange={(e) => handleInputChange("cardNumber", e.target.value)}
                            className={`h-12 ${errors.cardNumber ? "border-red-500" : ""}`}
                            maxLength={19} // 16 digits + 3 spaces
                        />
                        {errors.cardNumber && <p className="text-sm text-red-500">{errors.cardNumber}</p>}
                    </div>

                    {/* Expiry Date and CVV */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="expiryDate" className="text-base font-medium flex items-center gap-2">
                                Expiry date
                                <HelpCircle className="h-4 w-4 text-muted-foreground" />
                            </Label>
                            <Input
                                id="expiryDate"
                                type="text"
                                placeholder="MM/YY"
                                value={formatExpiryDate(formData.expiryDate)}
                                onChange={(e) => handleInputChange("expiryDate", e.target.value)}
                                className={`h-12 ${errors.expiryDate ? "border-red-500" : ""}`}
                                maxLength={5}
                            />
                            {errors.expiryDate && <p className="text-sm text-red-500">{errors.expiryDate}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="cvv" className="text-base font-medium flex items-center gap-2">
                                CVV
                                <HelpCircle className="h-4 w-4 text-muted-foreground" />
                            </Label>
                            <Input
                                id="cvv"
                                type="text"
                                placeholder="123"
                                value={formData.cvv}
                                onChange={(e) => handleInputChange("cvv", e.target.value.replace(/\D/g, ""))}
                                className={`h-12 ${errors.cvv ? "border-red-500" : ""}`}
                                maxLength={4}
                            />
                            {errors.cvv && <p className="text-sm text-red-500">{errors.cvv}</p>}
                        </div>
                    </div>

                    {/* Nickname */}
                    <div className="space-y-2">
                        <Label htmlFor="nickname" className="text-base font-medium">
                            Nickname(optional)
                        </Label>
                        <Input
                            id="nickname"
                            type="text"
                            placeholder="My main card"
                            value={formData.nickname}
                            onChange={(e) => handleInputChange("nickname", e.target.value)}
                            className="h-12"
                        />
                    </div>

                    {/* Payment Method Logos */}
                    <div className="flex items-center gap-3 py-4">
                        <div className="flex items-center justify-center w-12 h-8 bg-purple-600 rounded text-white text-xs font-bold">
                            CLUB
                        </div>
                        <div className="flex items-center justify-center w-12 h-8 bg-blue-600 rounded text-white text-xs font-bold">
                            VISA
                        </div>
                        <div className="flex items-center justify-center w-12 h-8 bg-gradient-to-r from-red-500 to-orange-500 rounded text-white text-xs font-bold">
                            MC
                        </div>
                        <div className="flex items-center justify-center w-12 h-8 bg-blue-500 rounded text-white text-xs font-bold">
                            AMEX
                        </div>
                        <div className="flex items-center justify-center w-12 h-8 bg-green-500 rounded text-white text-xs font-bold">
                            UNI
                        </div>
                    </div>

                    {/* Security Message */}
                    <div className="flex items-center justify-center gap-2 py-6">
                        <Shield className="h-5 w-5 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">Your payment info is stored securely</p>
                    </div>

                    {/* Submit Button */}
                    <Button type="submit" className="w-full h-12 text-base font-medium">
                        Add Card
                    </Button>
                </form>
            </div>
        </div>
    )
}
