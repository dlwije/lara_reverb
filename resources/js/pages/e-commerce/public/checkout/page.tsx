"use client"

import { useState } from "react"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import PublicLayout from '@/pages/e-commerce/public/layout';
import CheckoutSummary from '@/pages/e-commerce/public/checkout/checkout-summary';
import { useCart } from '@/contexts/CartContext';

interface CartItem {
    id: string
    name: string
    description: string
    price: number
    image?: string
}

const CheckoutPage = () => {
    const { cart, loading, applyPromoCode } = useCart(); // Use cart from context
    const [couponCode, setCouponCode] = useState("")
    const [discountAmount, setDiscountAmount] = useState(9.0)
    const [expandedAccordion, setExpandedAccordion] = useState("personal-details")

    const shippingCost = 10.66;

    // Calculate totals from actual cart data
    const subtotal = parseFloat(cart.subtotal) || 0;
    const tax = parseFloat(cart.tax) || 0;
    const discount = parseFloat(cart.discount) || 0;
    const total = subtotal + tax + shippingCost - discount;

    return (
        <PublicLayout>
        <div className="min-h-screen bg-background p-4 md:p-8">
            <div className="mx-auto max-w-7xl">
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Left Column - Form Sections */}
                    <div>
                        <div className="space-y-2 mb-4">
                            <h1 className="text-2xl font-bold text-foreground">Checkout</h1>
                            <p className="text-sm text-muted-foreground">Complete your purchase in 3 steps</p>
                        </div>

                        <Accordion
                            type="single"
                            collapsible
                            value={expandedAccordion}
                            onValueChange={setExpandedAccordion}
                            className="space-y-4"
                        >
                            {/* Personal Details Section */}
                            <AccordionItem
                                value="personal-details"
                                className="border border-border rounded-lg data-[state=open]:bg-card"
                            >
                                <AccordionTrigger className="px-6 py-4 hover:no-underline hover:bg-muted/50">
                                    <span className="font-semibold text-foreground">Your Personal Details</span>
                                </AccordionTrigger>
                                <AccordionContent className="px-6 pb-6 pt-2 border-t border-border">
                                    <div className="space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="firstName" className="text-sm font-medium text-foreground">
                                                    First Name
                                                </Label>
                                                <Input
                                                    id="firstName"
                                                    placeholder="First Name"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="lastName" className="text-sm font-medium text-foreground">
                                                    Last Name
                                                </Label>
                                                <Input
                                                    id="lastName"
                                                    placeholder="Last Name"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="email" className="text-sm font-medium text-foreground">
                                                    Email Address
                                                </Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    placeholder="Email address"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="phone" className="text-sm font-medium text-foreground">
                                                    Phone
                                                </Label>
                                                <Input
                                                    id="phone"
                                                    type="tel"
                                                    placeholder="Phone"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="mailingAddress" className="text-sm font-medium text-foreground">
                                                Mailing Address
                                            </Label>
                                            <Input
                                                id="mailingAddress"
                                                placeholder="Mailing Address"
                                                className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                            />
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="city" className="text-sm font-medium text-foreground">
                                                    City
                                                </Label>
                                                <Input
                                                    id="city"
                                                    placeholder="City"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="postCode" className="text-sm font-medium text-foreground">
                                                    Post Code
                                                </Label>
                                                <Input
                                                    id="postCode"
                                                    placeholder="Post Code"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="country" className="text-sm font-medium text-foreground">
                                                    Country
                                                </Label>
                                                <Input
                                                    id="country"
                                                    placeholder="Country"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="state" className="text-sm font-medium text-foreground">
                                                    Region/State
                                                </Label>
                                                <Select>
                                                    <SelectTrigger className="border-input bg-background text-foreground">
                                                        <SelectValue placeholder="Select a state" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="ca">California</SelectItem>
                                                        <SelectItem value="ny">New York</SelectItem>
                                                        <SelectItem value="tx">Texas</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>

                                        <Button
                                            onClick={() => setExpandedAccordion("shipping-address")}
                                            className="mt-4 w-full bg-foreground text-background hover:bg-muted-foreground"
                                        >
                                            Next Step
                                        </Button>
                                    </div>
                                </AccordionContent>
                            </AccordionItem>

                            {/* Shipping Address Section */}
                            <AccordionItem
                                value="shipping-address"
                                className="border border-border rounded-lg data-[state=open]:bg-card"
                            >
                                <AccordionTrigger className="px-6 py-4 hover:no-underline hover:bg-muted/50">
                                    <span className="font-semibold text-foreground">Shipping Address</span>
                                </AccordionTrigger>
                                <AccordionContent className="px-6 pb-6 pt-2 border-t border-border">
                                    <div className="space-y-4">
                                        <p className="text-sm text-muted-foreground">
                                            Enter the address where you'd like to receive your order.
                                        </p>

                                        <div className="space-y-2">
                                            <Label htmlFor="shipAddress" className="text-sm font-medium text-foreground">
                                                Street Address
                                            </Label>
                                            <Input
                                                id="shipAddress"
                                                placeholder="Street Address"
                                                className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                            />
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="shipCity" className="text-sm font-medium text-foreground">
                                                    City
                                                </Label>
                                                <Input
                                                    id="shipCity"
                                                    placeholder="City"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="shipPostCode" className="text-sm font-medium text-foreground">
                                                    Post Code
                                                </Label>
                                                <Input
                                                    id="shipPostCode"
                                                    placeholder="Post Code"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                        </div>

                                        <Button
                                            onClick={() => setExpandedAccordion("payment-info")}
                                            className="mt-4 w-full bg-foreground text-background hover:bg-muted-foreground"
                                        >
                                            Next Step
                                        </Button>
                                    </div>
                                </AccordionContent>
                            </AccordionItem>

                            {/* Payment Info Section */}
                            <AccordionItem value="payment-info" className="border border-border rounded-lg data-[state=open]:bg-card">
                                <AccordionTrigger className="px-6 py-4 hover:no-underline hover:bg-muted/50">
                                    <span className="font-semibold text-foreground">Payment Info</span>
                                </AccordionTrigger>
                                <AccordionContent className="px-6 pb-6 pt-2 border-t border-border">
                                    <div className="space-y-4">
                                        <p className="text-sm text-muted-foreground">Enter your payment details securely.</p>

                                        <div className="space-y-2">
                                            <Label htmlFor="cardName" className="text-sm font-medium text-foreground">
                                                Cardholder Name
                                            </Label>
                                            <Input
                                                id="cardName"
                                                placeholder="Name on card"
                                                className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="cardNumber" className="text-sm font-medium text-foreground">
                                                Card Number
                                            </Label>
                                            <Input
                                                id="cardNumber"
                                                placeholder="4532 1234 5678 9010"
                                                className="border-input bg-background text-foreground placeholder:text-muted-foreground font-mono"
                                            />
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="expiry" className="text-sm font-medium text-foreground">
                                                    Expiry Date
                                                </Label>
                                                <Input
                                                    id="expiry"
                                                    placeholder="MM/YY"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="cvc" className="text-sm font-medium text-foreground">
                                                    CVC
                                                </Label>
                                                <Input
                                                    id="cvc"
                                                    placeholder="123"
                                                    type="password"
                                                    className="border-input bg-background text-foreground placeholder:text-muted-foreground"
                                                />
                                            </div>
                                        </div>

                                        <Button className="mt-6 w-full bg-foreground text-background hover:bg-muted-foreground">
                                            Complete Purchase
                                        </Button>
                                    </div>
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </div>

                    {/* Right Column - Cart Summary */}
                    <CheckoutSummary />
                </div>
            </div>
        </div>
        </PublicLayout>
    )
}

export default CheckoutPage
