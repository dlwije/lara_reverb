import { useWishList } from '@/contexts/WishListContext';
import { Heart } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { CartItem } from '@/types/eCommerce/ecom.cart';
import { WishlistItem } from '@/types/eCommerce/homepage';

const HeartIcon = () => {
    const { wishlist, getWishList } = useWishList();
    const [cartItems, setCartItems] = useState<CartItem[]>([]);
    const [wishlistItems, setWishlistItems] = useState<WishlistItem[]>([]);

    const handleHeartIconClick = async () => {
        await getWishList();
    }

    const cartItemCount = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    const wishlistItemCount = wishlistItems.length;
    const cartTotal = cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0);

    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button variant="ghost" size="sm" className="relative h-9">
                    <Heart className="h-4 w-4" />
                    {wishlist.count > 0 && (
                        <Badge
                            variant="destructive"
                            className="absolute -right-1 -top-1 flex h-4 w-4 min-w-0 items-center justify-center p-0 text-xs"
                        >
                            {wishlist.count}
                        </Badge>
                    )}
                    {/*<span className="hidden sm:inline ml-1">Wishlist</span>*/}
                </Button>
            </SheetTrigger>
            <SheetContent className="sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>My Wishlist ({wishlist.count})</SheetTitle>
                </SheetHeader>
                <div className="mt-6">
                    {wishlistItems.length === 0 ? (
                        <div className="py-8 text-center">
                            <Heart className="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                            <p className="text-muted-foreground">Your wishlist is empty</p>
                            <Button className="mt-4" asChild>
                                <Link href="/products">Start Shopping</Link>
                            </Button>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {wishlistItems.map((item) => (
                                <div key={item.id} className="flex items-center space-x-3 rounded-lg border p-3">
                                    <img
                                        src={item.image || '/images/placeholder-product.jpg'}
                                        alt={item.name}
                                        className="h-12 w-12 rounded object-cover"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <h4 className="text-foreground truncate text-sm font-medium">{item.name}</h4>
                                        <p className="text-primary text-sm font-semibold">${item.price}</p>
                                    </div>
                                    <Button size="sm" variant="outline" asChild>
                                        <Link href={`/product/${item.slug}`}>View</Link>
                                    </Button>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    )
}

export default HeartIcon;
