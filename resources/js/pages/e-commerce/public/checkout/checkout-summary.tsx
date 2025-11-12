import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useCart } from '@/contexts/CartContext';
import { useState } from 'react';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';

const CheckoutSummaryPage = () => {
    const [shippingMethod, setShippingMethod] = useState<'standard' | 'express'>('standard');
    const [couponCode, setCouponCode] = useState("")
    const [discountAmount, setDiscountAmount] = useState(0)

    const shippingCost = shippingMethod === 'standard' ? 5.99 : 12.99;

    const {
        cart,
        loading,
        updateQuantity,
        removeFromCart,
        clearCart
    } = useCart();

    const handleApplyDiscount = () => {
        if (couponCode === "SAVE10") {
            setDiscountAmount(10.0)
        } else {
            setDiscountAmount(0)
        }
    }

    const getProductImage = (item: any) => {
        return item.product?.photo || item.options?.image || '/placeholder.svg';
    };

    const getProductName = (item: any) => {
        return item.name || item.product?.name || `Product ${item.id}`;
    };

    const getVariantInfo = (item: any) => {
        const parts = [];
        if (item.options?.size) parts.push(item.options.size);
        if (item.options?.color) parts.push(item.options.color);
        return parts.join(' • ') || 'Standard';
    };

    // Calculate totals from actual cart data
    const subtotal = parseFloat(cart.subtotal) || 0;
    const tax = parseFloat(cart.tax) || 0;
    const discount = parseFloat(cart.discount) || discountAmount;
    const total = subtotal + tax + shippingCost - discount;

    return (
        <div className="space-y-6">
            {/* Coupon Section */}
            <Card className="border border-border p-4">
                <h2 className="-mb-5 text-lg font-semibold text-foreground">Coupon Code</h2>
                <p className=" text-sm text-muted-foreground">Enter code to get discount instantly</p>

                <div className="flex gap-2">
                    <Input
                        placeholder="Add discount code"
                        value={couponCode}
                        onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                        className="flex-1"
                    />
                    <Button
                        onClick={handleApplyDiscount}
                        className="bg-foreground text-background hover:bg-muted-foreground"
                    >
                        Apply
                    </Button>
                </div>
            </Card>

            {/* Shopping Cart */}
            <Card className="border border-border p-4">
                <h2 className="-mb-5 text-lg font-semibold text-foreground">Shopping Cart</h2>
                <p className="mb-2 text-sm text-muted-foreground">You have {cart.content.length} items in your cart</p>

                <div className="space-y-4">
                    {cart.content.map((item) => (
                        <div key={item.rowId} className="flex gap-4 border-b border-border pb-4 last:border-0">
                            <div className="h-20 w-20 flex-shrink-0 overflow-hidden rounded-md bg-muted">
                                <img
                                    src={getProductImage(item)}
                                    alt={getProductName(item)}
                                    className="h-full w-full object-cover"
                                    onError={(e) => {
                                        (e.target as HTMLImageElement).src = '/placeholder.svg';
                                    }}
                                />
                            </div>
                            <div className="flex-1">
                                <h3 className="font-medium text-foreground">{getProductName(item)}</h3>
                                <p className="text-sm text-muted-foreground">{getVariantInfo(item)}</p>
                                {item.options && Object.keys(item.options).length > 0 && (
                                    <div className="text-muted-foreground mt-1 text-xs">
                                        {Object.entries(item.options)
                                            .filter(([key]) => !['image', 'color', 'size'].includes(key))
                                            .map(([key, value]) => (
                                                <span key={key} className="mr-2">
                                                                    {key}: {value}
                                                                </span>
                                            ))}
                                    </div>
                                )}
                            </div>
                            <div className="text-right font-semibold text-foreground">${parseFloat(item.price).toFixed(2)}</div>
                        </div>
                    ))}
                </div>

                {/* Shipping Method */}
                <div>
                    <h3 className="text-foreground mb-3 text-sm font-semibold">Shipping Method</h3>
                    <RadioGroup
                        value={shippingMethod}
                        onValueChange={(value) => setShippingMethod(value as 'standard' | 'express')}
                    >
                        <Card className="border-border bg-background mb-2 p-4">
                            <div className="flex items-center justify-between">
                                <div className="flex flex-1 items-center gap-3">
                                    <RadioGroupItem value="standard" id="standard" />
                                    <Label htmlFor="standard" className="flex-1 cursor-pointer">
                                        <div className="text-foreground font-medium">Standard Shipping</div>
                                        <div className="text-muted-foreground text-sm">3-5 days</div>
                                    </Label>
                                </div>
                                <div className="text-foreground font-semibold">$5.99</div>
                            </div>
                        </Card>
                        <Card className="border-border bg-background p-4">
                            <div className="flex items-center justify-between">
                                <div className="flex flex-1 items-center gap-3">
                                    <RadioGroupItem value="express" id="express" />
                                    <Label htmlFor="express" className="flex-1 cursor-pointer">
                                        <div className="text-foreground font-medium">Express Shipping</div>
                                        <div className="text-muted-foreground text-sm">1-2 days</div>
                                    </Label>
                                </div>
                                <div className="text-foreground font-semibold">$12.99</div>
                            </div>
                        </Card>
                    </RadioGroup>
                </div>

                {/* Pricing Summary */}
                <div className="mt-6 space-y-3 border-t border-border pt-6">
                    <div className="flex justify-between text-sm">
                        <span className="text-muted-foreground">Subtotal</span>
                        <span className="text-foreground font-medium">${subtotal.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                        <span className="text-muted-foreground">Shipping Cost (+)</span>
                        <span className="text-foreground font-medium">${shippingCost.toFixed(2)}</span>
                    </div>
                    {discountAmount > 0 && (
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Discount (-)</span>
                            <span className="text-foreground font-medium">${discountAmount.toFixed(2)}</span>
                        </div>
                    )}
                    <div className="flex justify-between border-t border-border pt-3">
                        <span className="font-semibold text-foreground">Total Payable</span>
                        <span className="text-lg font-bold text-foreground">${total.toFixed(2)}</span>
                    </div>
                </div>

                <Button className="mt-6 w-full bg-foreground text-background hover:bg-muted-foreground">
                    Place Order
                </Button>

                <p className="mt-4 text-xs text-muted-foreground text-center">
                    By placing your order, you agree to our company{" "}
                    <a href="#" className="underline hover:no-underline">
                        Privacy Policy
                    </a>{" "}
                    and{" "}
                    <a href="#" className="underline hover:no-underline">
                        Conditions of use
                    </a>
                </p>
            </Card>
        </div>
    )
}

export default CheckoutSummaryPage;
