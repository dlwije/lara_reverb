import { useCart } from '@/contexts/CartContext';
import { Link } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { ShoppingCart } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const CartIcon = () => {
    const { cart, getCart } = useCart();

    const handleCartClick = async () => {
        // Refresh cart data when icon is clicked
        await getCart();
    };

    return (
        <Button variant="ghost" size="icon" asChild>
            <Link href={route('cart')} onClick={handleCartClick} className="relative">
                <ShoppingCart className="h-5 w-5" />
                {cart.count > 0 && (
                    <Badge
                        variant="secondary"
                        className="absolute -top-2 -right-2 h-5 w-5 min-w-0 p-0 flex items-center justify-center text-xs"
                    >
                        {cart.count}
                    </Badge>
                )}
            </Link>
        </Button>
    );
};

export default CartIcon;
