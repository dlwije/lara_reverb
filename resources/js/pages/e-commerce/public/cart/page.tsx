import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import apiClient from '@/lib/apiClient';
import PublicLayout from '@/pages/e-commerce/public/layout';
import { Minus, Package, Plus, Shield, Trash2, Truck } from 'lucide-react';
import { useEffect, useState } from 'react';
import CartListPage from '@/pages/e-commerce/public/cart/cart-list';
import { useCart } from '@/contexts/CartContext';
import { toast } from 'sonner';

interface CartPageProps {
    cart: any;
}

const CartPage = ({ cart: initialCart }: CartPageProps) => {
    const { cart, loading, applyPromoCode } = useCart(); // Use cart from context
    const [promoCode, setPromoCode] = useState('');
    const [checkoutLoading, setCheckoutLoading] = useState(false);

    // Calculate totals from actual cart data
    const subtotal = parseFloat(cart.subtotal) || 0;
    const tax = parseFloat(cart.tax) || 0;
    const discount = parseFloat(cart.discount) || 0;
    const total = subtotal + tax - discount;

    const handleApplyPromoCode = async () => {
        if (!promoCode.trim()) return;

        const success = await applyPromoCode(promoCode);
        if (success) {
            setPromoCode('');
        }
    };

    // Handle Enter key for promo code
    const handlePromoKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleApplyPromoCode();
        }
    };

    const proceedToCheckout = async () => {
        if (cart.content.length === 0) return;

        setCheckoutLoading(true);
        try {
            // Check if user is authenticated
            const authCheck = await apiClient.get('api/auth/check');
            console.log(authCheck.data);
            const isAuthenticated = authCheck.data.authenticated;

            // if (isAuthenticated) {
                // User is logged in - proceed directly to checkout
                // window.location.href = route('getCheckout');

                const existing = localStorage.getItem("cart_token");
                const params = {
                    identifier: existing
                }
                // Store cart to database first
                const response = await apiClient.post('/cart/store-to-database', params);

                const respData = response.data;
                // console.log(respData);
                if (respData.status) {

                    if (respData?.data?.identifier && !existing) {
                        // console.log(respData.data?.identifier);
                        localStorage.setItem("cart_token", respData.data?.identifier);
                        // console.log("Cart Token saved to localStorage");
                    }
                    // Redirect to checkout with cart ID
                    window.location.href = `/checkout?cart_id=${respData.data?.identifier}`;
                } else {
                    toast.error("Failed to proceed to checkout");
                }
            // } else {
            //     // User is not logged in - redirect to login with cart preservation
            //     // Save cart to temporary storage or keep in session
            //     window.location.href = '/login?redirect=checkout';
            // }
        } catch (error) {
            alert('Error checking authentication');
            console.error('Error checking authentication:', error);
            // Fallback - redirect to login
            // window.location.href = '/login?redirect=checkout';
        } finally {
            setCheckoutLoading(false);
        }
    };

    return (
        <PublicLayout>
            <section className="bg-background dark min-h-screen">
                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-8 md:grid-cols-[2fr_1fr]">
                        <CartListPage />

                        {/* Order Summary */}
                        <div className="lg:sticky lg:top-8 lg:self-start">
                            <Card className="border-border bg-card p-6">
                                <h2 className="text-foreground -mb-5 text-2xl font-bold">Order Summary</h2>
                                <p className="text-muted-foreground mb-2 text-sm">Review your order details</p>

                                <div className="space-y-6">


                                    {/* Promo Code */}
                                    {/*<div>*/}
                                    {/*    <h3 className="text-foreground mb-3 text-sm font-semibold">Promo Code</h3>*/}
                                    {/*    <div className="flex gap-2">*/}
                                    {/*        <Input*/}
                                    {/*            placeholder="Enter promo code"*/}
                                    {/*            value={promoCode}*/}
                                    {/*            onChange={(e) => setPromoCode(e.target.value)}*/}
                                    {/*            onKeyPress={handlePromoKeyPress}*/}
                                    {/*            disabled={loading}*/}
                                    {/*            className="border-border bg-background text-foreground placeholder:text-muted-foreground"*/}
                                    {/*        />*/}
                                    {/*        <Button*/}
                                    {/*            variant="default"*/}
                                    {/*            onClick={handleApplyPromoCode}*/}
                                    {/*            disabled={loading || !promoCode.trim()}*/}
                                    {/*            className="whitespace-nowrap"*/}
                                    {/*        >*/}
                                    {/*            {loading ? 'Applying...' : 'Apply'}*/}
                                    {/*        </Button>*/}
                                    {/*    </div>*/}
                                    {/*</div>*/}

                                    {/* Order Total */}
                                    <div className="border-border space-y-2 border-t pt-4">
                                        <div className="text-foreground flex justify-between">
                                            <span>Subtotal {cart.count} {cart.count === 1 ? 'item' : 'items'})</span>
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
                                        {/*<div className="text-foreground flex justify-between">*/}
                                        {/*    <span>Shipping</span>*/}
                                        {/*    <span className="font-semibold">${shippingCost.toFixed(2)}</span>*/}
                                        {/*</div>*/}
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
                                    <Button
                                        className="w-full"
                                        size="lg"
                                        disabled={cart.content.length === 0 || loading || checkoutLoading}
                                        onClick={proceedToCheckout}
                                    >
                                        <Package className="mr-2 h-5 w-5" />
                                        {checkoutLoading ? 'Checking...' : 'Proceed to Checkout'}
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
