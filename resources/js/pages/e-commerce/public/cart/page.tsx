import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import apiClient from '@/lib/apiClient';
import PublicLayout from '@/pages/e-commerce/public/layout';
import { CartData, CartItem } from '@/types/eCommerce/ecom.cart';
import { Minus, Package, Plus, Shield, Trash2, Truck } from 'lucide-react';
import { useEffect, useState } from 'react';

interface CartPageProps {
    cart: CartData;
}

const CartPage = ({ cart: initialCart }: CartPageProps) => {
    const [cart, setCart] = useState<CartData>(initialCart);
    const [promoCode, setPromoCode] = useState('');
    const [shippingMethod, setShippingMethod] = useState<'standard' | 'express'>('standard');
    const [loading, setLoading] = useState(false);
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const shippingCost = shippingMethod === 'standard' ? 5.99 : 12.99;

    // Update cart if initialCart changes
    useEffect(() => {
        setCart(initialCart);
    }, [initialCart]);

    const updateQuantity = async (rowId: string, newQty: number) => {
        if (newQty < 1) return;

        setActionLoading(`update-${rowId}`);
        try {
            const params = new URLSearchParams({
                qty: newQty.toString(),
            });

            const response = await apiClient.put(`/cart/update/${rowId}?${params}`);

            if (response.data.success) {
                setCart(response.data.data);
            } else {
                console.error('Failed to update quantity');
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
        } finally {
            setActionLoading(null);
        }
    };

    const removeItem = async (rowId: string) => {
        setActionLoading(`remove-${rowId}`);
        try {
            const response = await apiClient.delete(`/cart/remove/${rowId}`);

            if (response.data.success) {
                setCart(response.data.data);
            } else {
                console.error('Failed to remove item');
            }
        } catch (error) {
            console.error('Error removing item:', error);
        } finally {
            setActionLoading(null);
        }
    };

    const applyPromoCode = async () => {
        if (!promoCode.trim()) return;

        setLoading(true);
        try {
            const params = new URLSearchParams({
                promo_code: promoCode,
            });

            const response = await apiClient.post(`/cart/apply-promo?${params}`);

            if (response.data.success) {
                setCart(response.data.data);
                setPromoCode('');
            } else {
                alert(response.data.message || 'Failed to apply promo code');
            }
        } catch (error) {
            console.error('Error applying promo code:', error);
        } finally {
            setLoading(false);
        }
    };

    const clearCart = async () => {
        if (!confirm('Are you sure you want to clear your cart?')) return;

        setLoading(true);
        try {
            const response = await apiClient.delete('/cart/clear');

            if (response.data.success) {
                setCart(response.data.data);
            } else {
                console.error('Failed to clear cart');
            }
        } catch (error) {
            console.error('Error clearing cart:', error);
        } finally {
            setLoading(false);
        }
    };

    const getProductImage = (item: CartItem) => {
        return item.product?.image || item.options?.image || '/placeholder.svg';
    };

    const getProductName = (item: CartItem) => {
        return item.name || item.product?.name || `Product ${item.id}`;
    };

    const getVariantInfo = (item: CartItem) => {
        const parts = [];
        if (item.options?.size) parts.push(item.options.size);
        if (item.options?.color) parts.push(item.options.color);
        return parts.join(' • ') || 'Standard';
    };

    // Calculate totals from actual cart data
    const subtotal = parseFloat(cart.subtotal) || 0;
    const tax = parseFloat(cart.tax) || 0;
    const discount = parseFloat(cart.discount) || 0;
    const total = subtotal + tax + shippingCost - discount;

    // Handle Enter key for promo code
    const handlePromoKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            applyPromoCode();
        }
    };

    return (
        <PublicLayout>
            <section className="bg-background dark min-h-screen">
                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-8 md:grid-cols-[2fr_1fr]">
                        {/* Cart Items */}
                        <div>
                            <div className="mb-6 flex items-center justify-between">
                                <div>
                                    <h1 className="text-foreground text-3xl font-bold">Shopping Cart</h1>
                                    <p className="text-muted-foreground">
                                        {cart.count} {cart.count === 1 ? 'item' : 'items'} in your cart
                                    </p>
                                </div>
                                {cart.content.length > 0 && (
                                    <Button
                                        variant="outline"
                                        onClick={clearCart}
                                        disabled={loading}
                                        className="text-destructive border-destructive hover:bg-destructive hover:text-white"
                                    >
                                        {loading ? 'Clearing...' : 'Clear Cart'}
                                    </Button>
                                )}
                            </div>

                            {cart.content.length === 0 ? (
                                <Card className="border-border bg-card p-8 text-center">
                                    <Package className="text-muted-foreground mx-auto mb-4 h-16 w-16" />
                                    <h3 className="text-foreground mb-2 text-xl font-semibold">Your cart is empty</h3>
                                    <p className="text-muted-foreground mb-4">Add some products to get started</p>
                                    <Button asChild>
                                        <a href={route('front.products')}>Continue Shopping</a>
                                    </Button>
                                </Card>
                            ) : (
                                <div className="space-y-4">
                                    {cart.content.map((item) => (
                                        <Card key={item.rowId} className="border-border bg-card overflow-hidden p-4">
                                            <div className="flex gap-4">
                                                <div className="bg-muted h-32 w-32 flex-shrink-0 overflow-hidden rounded-lg">
                                                    <img
                                                        src={getProductImage(item)}
                                                        alt={getProductName(item)}
                                                        className="h-full w-full object-cover"
                                                        onError={(e) => {
                                                            (e.target as HTMLImageElement).src = '/placeholder.svg';
                                                        }}
                                                    />
                                                </div>

                                                <div className="flex flex-1 flex-col justify-between">
                                                    <div className="flex justify-between">
                                                        <div>
                                                            <h3 className="text-foreground text-lg font-semibold">{getProductName(item)}</h3>
                                                            <p className="text-muted-foreground text-sm">{getVariantInfo(item)}</p>
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
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => removeItem(item.rowId)}
                                                            disabled={actionLoading === `remove-${item.rowId}`}
                                                            className="text-muted-foreground hover:text-destructive"
                                                        >
                                                            {actionLoading === `remove-${item.rowId}` ? (
                                                                <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                                            ) : (
                                                                <Trash2 className="h-5 w-5" />
                                                            )}
                                                        </Button>
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center gap-2">
                                                            <Button
                                                                variant="outline"
                                                                size="icon"
                                                                onClick={() => updateQuantity(item.rowId, item.qty - 1)}
                                                                disabled={actionLoading === `update-${item.rowId}` || item.qty <= 1}
                                                                className="border-border bg-background h-9 w-9"
                                                            >
                                                                <Minus className="h-4 w-4" />
                                                            </Button>
                                                            <span className="text-foreground w-12 text-center">
                                                                {actionLoading === `update-${item.rowId}` ? (
                                                                    <div className="mx-auto h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                                                ) : (
                                                                    item.qty
                                                                )}
                                                            </span>
                                                            <Button
                                                                variant="outline"
                                                                size="icon"
                                                                onClick={() => updateQuantity(item.rowId, item.qty + 1)}
                                                                disabled={actionLoading === `update-${item.rowId}`}
                                                                className="border-border bg-background h-9 w-9"
                                                            >
                                                                <Plus className="h-4 w-4" />
                                                            </Button>
                                                        </div>

                                                        <div className="text-right">
                                                            <div className="text-foreground text-xl font-bold">
                                                                ${parseFloat(item.price).toFixed(2)}
                                                            </div>
                                                            <div className="text-muted-foreground text-sm">
                                                                ${parseFloat(item.total).toFixed(2)} total
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Order Summary */}
                        <div className="lg:sticky lg:top-8 lg:self-start">
                            <Card className="border-border bg-card p-6">
                                <h2 className="text-foreground -mb-5 text-2xl font-bold">Order Summary</h2>
                                <p className="text-muted-foreground mb-2 text-sm">Review your order details and shipping information</p>

                                <div className="space-y-6">
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

                                    {/* Promo Code */}
                                    <div>
                                        <h3 className="text-foreground mb-3 text-sm font-semibold">Promo Code</h3>
                                        <div className="flex gap-2">
                                            <Input
                                                placeholder="Enter promo code"
                                                value={promoCode}
                                                onChange={(e) => setPromoCode(e.target.value)}
                                                onKeyPress={handlePromoKeyPress}
                                                disabled={loading}
                                                className="border-border bg-background text-foreground placeholder:text-muted-foreground"
                                            />
                                            <Button
                                                variant="default"
                                                onClick={applyPromoCode}
                                                disabled={loading || !promoCode.trim()}
                                                className="whitespace-nowrap"
                                            >
                                                {loading ? 'Applying...' : 'Apply'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Order Total */}
                                    <div className="border-border space-y-2 border-t pt-4">
                                        <div className="text-foreground flex justify-between">
                                            <span>Subtotal</span>
                                            <span className="font-semibold">${subtotal.toFixed(2)}</span>
                                        </div>
                                        <div className="text-foreground flex justify-between">
                                            <span>Tax</span>
                                            <span className="font-semibold">${tax.toFixed(2)}</span>
                                        </div>
                                        {discount > 0 && (
                                            <div className="flex justify-between text-green-600">
                                                <span>Discount</span>
                                                <span className="font-semibold">-${discount.toFixed(2)}</span>
                                            </div>
                                        )}
                                        <div className="text-foreground flex justify-between">
                                            <span>Shipping</span>
                                            <span className="font-semibold">${shippingCost.toFixed(2)}</span>
                                        </div>
                                        <div className="border-border text-foreground flex justify-between border-t pt-2 text-lg font-bold">
                                            <span>Total</span>
                                            <span>${total.toFixed(2)}</span>
                                        </div>
                                    </div>

                                    {/* Features */}
                                    <div className="border-border space-y-3 border-t pt-4">
                                        <div className="text-foreground flex items-center gap-3 text-sm">
                                            <Package className="h-5 w-5" />
                                            <span>Free returns within 30 days</span>
                                        </div>
                                        <div className="text-foreground flex items-center gap-3 text-sm">
                                            <Shield className="h-5 w-5" />
                                            <span>Secure payment</span>
                                        </div>
                                        <div className="text-foreground flex items-center gap-3 text-sm">
                                            <Truck className="h-5 w-5" />
                                            <span>Fast delivery</span>
                                        </div>
                                    </div>

                                    {/* Checkout Button */}
                                    <Button className="w-full" size="lg" disabled={cart.content.length === 0 || loading}>
                                        <Package className="mr-2 h-5 w-5" />
                                        {loading ? 'Processing...' : 'Proceed to Checkout'}
                                    </Button>
                                </div>
                            </Card>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
};

export default CartPage;
