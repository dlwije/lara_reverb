
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Minus, Package, Plus, Trash2 } from 'lucide-react';
import { useCart } from '@/contexts/CartContext';
import toast from 'react-hot-toast';
import { Link } from '@inertiajs/react';
// import { useToast } from '@/hooks/use-toast';

const CartListPage = () => {
    const {
        cart,
        loading,
        updateQuantity,
        removeFromCart,
        clearCart
    } = useCart();

    // console.log(cart.content);

    const handleUpdateQuantity = async (rowId: string, newQty: number) => {
        if (newQty < 1) return;
        // console.log(newQty);
        const result = await updateQuantity(rowId, newQty);

        console.log(result.success);
        if (!result.success) {
            console.log('desads')
            alert(result.message)
            toast.error('Coupon copied to clipboard!');
            // toast.success(`New message from`, {
            //     // description: 'description',
            //     duration: 4000,
            //     position: 'top-right',
            //     action: {
            //         label: 'View',
            //         onClick: () => {
            //             // You can add navigation logic here
            //             console.log('Navigating to conversation:');
            //         }
            //     }
            // });
        }
    };

    const handleRemoveItem = async (rowId: string) => {
        await removeFromCart(rowId);
    };

    const handleClearCart = async () => {
        if (!confirm('Are you sure you want to clear your cart?')) return;
        await clearCart();
    };

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
    return (
        <>
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
                            onClick={handleClearCart}
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
                        {cart.content.map((item: any) => (
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
                                                <h3 className="text-foreground text-lg font-semibold"><Link href={`/product/${item.product.slug}`}>{getProductName(item)}</Link></h3>
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
                                                onClick={() => handleRemoveItem(item.rowId)}
                                                disabled={loading}
                                                className="text-muted-foreground hover:text-destructive"
                                            >
                                                {loading ? (
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
                                                    onClick={() => handleUpdateQuantity(item.rowId, parseFloat(item.qty) - 1)}
                                                    disabled={loading || item.qty <= 1}
                                                    className="border-border bg-background h-9 w-9"
                                                >
                                                    <Minus className="h-4 w-4" />
                                                </Button>
                                                <span className="text-foreground w-12 text-center">
                                                    {loading ? (
                                                        <div className="mx-auto h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                                    ) : (
                                                        item.qty
                                                    )}
                                                </span>
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={() => handleUpdateQuantity(item.rowId, parseFloat(item.qty) + 1)}
                                                    disabled={loading}
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
        </>
    )
}

export default CartListPage;
