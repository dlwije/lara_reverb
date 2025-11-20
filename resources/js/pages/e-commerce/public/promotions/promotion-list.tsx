// resources/js/Pages/e-commerce/public/promotions/promotion-list.jsx

import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Clock, Tag, ShoppingBag, Percent } from 'lucide-react';
import PaginationBottom from '@/components/e-commerce/public/paginationBottom';
import PublicLayout from '@/pages/e-commerce/public/layout';

export default function PromotionList() {
    const { promotions, promoted_products, pagination, stores, custom_fields } = usePage().props;

    const [activeTab, setActiveTab] = useState('all');

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    };

    const getDaysRemaining = (endDate) => {
        if (!endDate) return null;
        const end = new Date(endDate);
        const today = new Date();
        const diffTime = end - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays > 0 ? diffDays : 0;
    };

    const renderPromotionBadge = (promotionType) => {
        const badges = {
            discount: { label: 'Discount', color: 'bg-green-100 text-green-800' },
            buy_x_get_y: { label: 'Buy X Get Y', color: 'bg-blue-100 text-blue-800' },
            spend_and_save: { label: 'Spend & Save', color: 'bg-purple-100 text-purple-800' },
            coupon: { label: 'Coupon', color: 'bg-orange-100 text-orange-800' }
        };

        const badge = badges[promotionType] || { label: 'Sale', color: 'bg-red-100 text-red-800' };

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.color}`}>
                <Tag className="w-3 h-3 mr-1" />
                {badge.label}
            </span>
        );
    };

    return (
        <>
            <Head title="Deals & Promotions" />

            <PublicLayout>
                <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <div className="bg-white shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                        <div className="text-center">
                            <h1 className="text-3xl font-bold text-gray-900">Deals & Promotions</h1>
                            <p className="mt-2 text-lg text-gray-600">
                                Discover amazing offers and limited-time deals
                            </p>
                        </div>
                    </div>
                </div>

                {/* Tabs */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div className="flex space-x-4 border-b border-gray-200">
                        <button
                            onClick={() => setActiveTab('all')}
                            className={`py-2 px-4 border-b-2 font-medium text-sm ${
                                activeTab === 'all'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            All Deals
                        </button>
                        <button
                            onClick={() => setActiveTab('discounts')}
                            className={`py-2 px-4 border-b-2 font-medium text-sm ${
                                activeTab === 'discounts'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            Discounts
                        </button>
                        <button
                            onClick={() => setActiveTab('bundles')}
                            className={`py-2 px-4 border-b-2 font-medium text-sm ${
                                activeTab === 'bundles'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            Bundles
                        </button>
                    </div>
                </div>

                {/* Promotion Sections */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    {/* Featured Promotions */}
                    {promotions.length > 0 && (
                        <div className="mb-12">
                            <h2 className="text-2xl font-bold text-gray-900 mb-6">Featured Promotions</h2>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {promotions.map((promotion) => (
                                    <div key={promotion.id} className="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                                        <div className="p-6">
                                            <div className="flex items-center justify-between mb-4">
                                                {renderPromotionBadge(promotion.type)}
                                                {promotion.end_date && (
                                                    <div className="flex items-center text-sm text-orange-600">
                                                        <Clock className="w-4 h-4 mr-1" />
                                                        {getDaysRemaining(promotion.end_date)} days left
                                                    </div>
                                                )}
                                            </div>

                                            <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                                {promotion.name}
                                            </h3>

                                            <p className="text-gray-600 mb-4">
                                                {promotion.details}
                                            </p>

                                            {promotion.promotion_rules.length > 0 && (
                                                <div className="mb-4">
                                                    {promotion.promotion_rules.map((rule, index) => (
                                                        <div key={index} className="flex items-center text-sm text-green-700 mb-1">
                                                            <Percent className="w-4 h-4 mr-2" />
                                                            {rule}
                                                        </div>
                                                    ))}
                                                </div>
                                            )}

                                            {promotion.products.length > 0 && (
                                                <div className="mt-4">
                                                    <h4 className="text-sm font-medium text-gray-900 mb-2">
                                                        Included Products ({promotion.products.length})
                                                    </h4>
                                                    <div className="space-y-2">
                                                        {promotion.products.slice(0, 3).map((product) => (
                                                            <Link
                                                                key={product.id}
                                                                href={`/product/${product.slug}`}
                                                                className="flex items-center space-x-3 p-2 rounded-md hover:bg-gray-50 transition-colors"
                                                            >
                                                                <img
                                                                    src={product.photo || '/placeholder-product.jpg'}
                                                                    alt={product.name}
                                                                    className="w-10 h-10 object-cover rounded"
                                                                />
                                                                <div className="flex-1 min-w-0">
                                                                    <p className="text-sm font-medium text-gray-900 truncate">
                                                                        {product.name}
                                                                    </p>
                                                                    <div className="flex items-center space-x-2">
                                                                        <span className="text-sm text-gray-500 line-through">
                                                                            {formatCurrency(product.original_price)}
                                                                        </span>
                                                                        <span className="text-sm font-semibold text-green-600">
                                                                            {formatCurrency(product.discounted_price)}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </Link>
                                                        ))}
                                                    </div>

                                                    {promotion.products.length > 3 && (
                                                        <Link
                                                            href={`/promotions/${promotion.id}`}
                                                            className="block mt-3 text-center text-sm text-blue-600 hover:text-blue-800 font-medium"
                                                        >
                                                            View all {promotion.products.length} products
                                                        </Link>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* All Promoted Products */}
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900 mb-6">All Products on Sale</h2>

                        {promoted_products.length > 0 ? (
                            <>
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                    {promoted_products.map((product) => (
                                        <div key={product.id} className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                                            <Link href={`/products/${product.slug}`}>
                                                <div className="relative">
                                                    <img
                                                        src={product.photo || '/placeholder-product.jpg'}
                                                        alt={product.name}
                                                        className="w-full h-48 object-cover"
                                                    />
                                                    {product.promotions && product.promotions.length > 0 && (
                                                        <div className="absolute top-2 left-2">
                                                            {renderPromotionBadge(product.promotions[0].type)}
                                                        </div>
                                                    )}
                                                    {product.discount_percentage > 0 && (
                                                        <div className="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-sm font-semibold">
                                                            -{product.discount_percentage}%
                                                        </div>
                                                    )}
                                                </div>
                                            </Link>

                                            <div className="p-4">
                                                <Link href={`/product/${product.slug}`}>
                                                    <h3 className="font-semibold text-gray-900 mb-2 hover:text-blue-600 transition-colors">
                                                        {product.name}
                                                    </h3>
                                                </Link>

                                                <div className="flex items-center justify-between mb-2">
                                                    <div className="flex items-center space-x-2">
                                                        <span className="text-lg font-bold text-gray-900">
                                                            {formatCurrency(product.discounted_price || product.price)}
                                                        </span>
                                                        {product.discounted_price && product.original_price > product.discounted_price && (
                                                            <span className="text-sm text-gray-500 line-through">
                                                                {formatCurrency(product.original_price)}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>

                                                <div className="flex items-center justify-between text-sm text-gray-600">
                                                    <span className={product.in_stock ? 'text-green-600' : 'text-red-600'}>
                                                        {product.in_stock ? 'In Stock' : 'Out of Stock'}
                                                    </span>
                                                    {product.brand && (
                                                        <span>{product.brand.name}</span>
                                                    )}
                                                </div>

                                                {product.promotions && product.promotions.length > 0 && (
                                                    <div className="mt-2">
                                                        <div className="flex flex-wrap gap-1">
                                                            {product.promotions.map((promo) => (
                                                                <span
                                                                    key={promo.id}
                                                                    className="inline-block bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded"
                                                                >
                                                                    {promo.name}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Pagination */}
                                <PaginationBottom pagination={pagination} />
                            </>
                        ) : (
                            <div className="text-center py-12">
                                <ShoppingBag className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No promotions available</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Check back later for amazing deals!
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
            </PublicLayout>
        </>
    );
}
