'use client';

import { debounce } from 'lodash';
import type React from 'react';
import { useCallback, useEffect, useRef, useState } from 'react';
import AutoComplete from '../../Components/AutoComplete';
// import PhotoInput from '../../Components/PhotoInput';
import AppLayout from '@/layouts/app-layout';
import { Product } from '@/types/product';

interface Unit {
    id: number;
    name: string;
    subunits?: Array<{ id: number; name: string }>;
}

interface Category {
    id: number;
    name: string;
}

interface Brand {
    id: number;
    name: string;
}

interface Store {
    id: number;
    name: string;
}

interface Tax {
    id: number;
    name: string;
    rate: number;
}

interface Serial {
    number: string;
    till: string;
}

interface Variant {
    name: string;
    options: string[];
}

interface CompleteFormProps {
    current?: any;
    brands: Brand[];
    categories: Category[];
    stores: Store[];
    taxes: Tax[];
    units: Unit[];
}

export default function CompleteForm({ current, brands = [], categories = [], stores = [], taxes = [], units = [] }: CompleteFormProps) {
    const [unit, setUnit] = useState<Unit | null>(null);
    const [result, setResult] = useState<Product[]>([]);
    const [search, setSearch] = useState<string>('');
    const [category, setCategory] = useState<Category | null>(null);
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);

    const photoInputRef = useRef<HTMLInputElement>(null);

    const [form, setForm] = useState<Product>({
        _method: current?.id ? 'put' : 'post',
        photo: null as File | null,
        photos: null as FileList | null,
        type: current?.type || 'Standard',
        name: current?.name || '',
        code: current?.code || '',
        symbology: current?.symbology || 'CODE128',
        brand_id: current?.brand_id || '',
        category_id: current?.category_id || '',
        unit_id: current?.unit_id || '',
        cost: current?.cost ? Number(current.cost) : null,
        price: current?.price ? Number(current.price) : null,
        tax_id: current?.tax_id || '',
        alert_quantity: current?.alert_quantity ? Number(current.alert_quantity) : null,
        details: current?.details || '',
        has_variants: current?.has_variants === 1,
        track_quantity: current?.track_quantity !== 0,
        can_sale: current?.can_sale !== 0,
        can_purchase: current?.can_purchase !== 0,
        hide: current?.hide === 1,
        products: current?.products || [],
        unit_prices: current?.unit_prices || {},
        dont_track_stock: current?.dont_track_stock === 1,
        serials: current?.serials || ([{ number: '', till: '' }] as Serial[]),
        variants: current?.variants || ([{ name: '', options: [''] }] as Variant[]),
        variations: current?.variations || [],
        set_stock: current?.stores ? current.stores.filter((s: any) => s.pivot.price) : false,
        slug: current?.slug || '',
        title: current?.title || '',
        description: current?.description || '',
        keywords: current?.keywords || '',
        noindex: current?.noindex === 1,
        nofollow: current?.nofollow === 1,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (current?.category_id) {
            const foundCategory = categories.find((c) => c.id === current.category_id);
            if (foundCategory) setCategory(foundCategory);
        }

        if (current?.unit_id) {
            const foundUnit = units.find((u) => u.id === current.unit_id);
            if (foundUnit) {
                setUnit(foundUnit);
                if (current?.unit_prices && Object.keys(current.unit_prices).length) {
                    const unitPrices = current.unit_prices.reduce((a: any, i: any) => {
                        a[i.unit_id] = { cost: i.cost ? Number(i.cost) : null, price: i.price ? Number(i.price) : null };
                        return a;
                    }, {});
                    setForm((prev) => ({ ...prev, unit_prices: unitPrices }));
                } else if (foundUnit?.subunits) {
                    const unitPrices = foundUnit.subunits.reduce((a: any, i: any) => {
                        a[i.id] = { cost: null, price: null };
                        return a;
                    }, {});
                    setForm((prev) => ({ ...prev, unit_prices: unitPrices }));
                }
            }
        }
    }, [current, categories, units]);

    const debouncedSearch = useCallback(
        debounce(async (q: string) => {
            if (!q) {
                setResult([]);
                return;
            }

            try {
                // Mock API call - replace with actual API
                const mockResults: Product[] = [
                    { id: 1, name: 'Sample Product 1', code: 'SP001', quantity: 1 },
                    { id: 2, name: 'Sample Product 2', code: 'SP002', quantity: 1 },
                ].filter((p) => p.name.toLowerCase().includes(q.toLowerCase()));

                if (mockResults.length === 1) {
                    addProduct(mockResults[0]);
                } else {
                    setResult(mockResults);
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 500),
        [],
    );

    useEffect(() => {
        debouncedSearch(search);
    }, [search, debouncedSearch]);

    const addProduct = async (product: Product) => {
        const existingProduct = form.products.find((p: Product) => p.id === product.id);

        if (existingProduct) {
            setForm((prev) => ({
                ...prev,
                products: prev.products.map((p: Product) => (p.id === product.id ? { ...p, quantity: p.quantity + 1 } : p)),
            }));
        } else {
            setForm((prev) => ({
                ...prev,
                products: [...prev.products, { ...product, quantity: 1 }],
            }));
        }

        setSearch('');
        setResult([]);
    };

    const removeProduct = (productId: number) => {
        setForm((prev) => ({
            ...prev,
            products: prev.products.filter((p: Product) => p.id !== productId),
        }));
    };

    const selectNewPhoto = () => {
        photoInputRef.current?.click();
    };

    const updatePhotoPreview = (file: File) => {
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            setPhotoPreview(e.target?.result as string);
        };
        reader.readAsDataURL(file);

        setForm((prev) => ({ ...prev, photo: file }));
    };

    const deletePhoto = () => {
        setPhotoPreview(null);
        setForm((prev) => ({ ...prev, photo: null }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        try {
            // Mock form submission - replace with actual API call
            console.log('Form submitted:', form);
            alert('Product saved successfully!');
        } catch (error) {
            console.error('Submission error:', error);
        } finally {
            setProcessing(false);
        }
    };

    return (
        <AppLayout>
            <div className="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <div className="overflow-hidden bg-white shadow-xl sm:rounded-lg dark:bg-gray-800">
                    <form onSubmit={handleSubmit} className="p-6">
                        <div className="grid grid-cols-6 gap-6">
                            {/* Product Type */}
                            <div className="col-span-6 sm:col-span-3">
                                <AutoComplete
                                    id="type"
                                    label="Type"
                                    value={form.type}
                                    error={errors.type}
                                    suggestions={[
                                        { value: 'Standard', label: 'Standard' },
                                        { value: 'Service', label: 'Service' },
                                        { value: 'Digital', label: 'Digital' },
                                        { value: 'Combo', label: 'Combo' },
                                        { value: 'Recipe', label: 'Recipe' },
                                    ]}
                                    onChange={(value) => setForm((prev) => ({ ...prev, type: value }))}
                                />
                            </div>

                            {/* Combo Products Section */}
                            {form.type === 'Combo' && (
                                <div className="col-span-full mb-6 rounded-sm border border-gray-200 p-6 dark:border-gray-700">
                                    <div className="relative">
                                        <input
                                            id="product-search"
                                            type="text"
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            placeholder="Scan barcode or type to search"
                                            className="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                        {search && result.length > 0 && (
                                            <div className="absolute left-0 right-0 top-full z-10 mt-2 rounded-md bg-white py-1 ring-1 dark:bg-gray-700 dark:ring-gray-700">
                                                {result.map((product) => (
                                                    <button
                                                        key={product.id}
                                                        type="button"
                                                        onClick={() => addProduct(product)}
                                                        className="w-full px-4 py-1.5 text-left hover:bg-gray-100 dark:hover:bg-gray-900"
                                                    >
                                                        {product.name}
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>

                                    {form.products.length > 0 && (
                                        <>
                                            <h4 className="mb-3 mt-6 text-lg font-bold">Combo Products</h4>
                                            <table className="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                <tbody>
                                                    {form.products.map((product: Product, index: number) => (
                                                        <tr key={product.code}>
                                                            <td className="w-8 py-2">{index + 1}.</td>
                                                            <td className="p-2">{product.name}</td>
                                                            <td className="w-36 py-2">
                                                                <input
                                                                    type="number"
                                                                    min="0"
                                                                    value={product.quantity}
                                                                    onChange={(e) => {
                                                                        const newQuantity = Number.parseInt(e.target.value) || 0;
                                                                        if (newQuantity === 0) {
                                                                            removeProduct(product.id);
                                                                        } else {
                                                                            setForm((prev) => ({
                                                                                ...prev,
                                                                                products: prev.products.map((p: Product) =>
                                                                                    p.id === product.id ? { ...p, quantity: newQuantity } : p,
                                                                                ),
                                                                            }));
                                                                        }
                                                                    }}
                                                                    className="w-full rounded border border-gray-300 px-2 py-1"
                                                                />
                                                            </td>
                                                            <td className="w-8 py-2">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => removeProduct(product.id)}
                                                                    className="text-red-600 hover:text-red-800"
                                                                >
                                                                    ×
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </>
                                    )}
                                </div>
                            )}

                            {/* Basic Product Information */}
                            <div className="col-span-6 sm:col-span-3">
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name</label>
                                <input
                                    type="text"
                                    value={form.name}
                                    onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                                {errors.name && <div className="mt-1 text-sm text-red-600">{errors.name}</div>}
                            </div>

                            <div className="col-span-6 sm:col-span-3">
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Code</label>
                                <input
                                    type="text"
                                    value={form.code}
                                    onChange={(e) => setForm((prev) => ({ ...prev, code: e.target.value }))}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>

                            {/* Photo Upload */}
                            <div className="col-span-6">
                                <PhotoInput label="Product Photo" accept="image/*" onChange={updatePhotoPreview} error={errors.photo} />
                                {photoPreview && (
                                    <div className="mt-2">
                                        <img src={photoPreview || '/placeholder.svg'} alt="Preview" className="h-20 w-20 rounded object-cover" />
                                        <button type="button" onClick={deletePhoto} className="ml-2 text-red-600 hover:text-red-800">
                                            Remove
                                        </button>
                                    </div>
                                )}
                            </div>

                            {/* Submit Button */}
                            <div className="col-span-6">
                                <Button type="submit" loading={processing}>
                                    {current?.id ? 'Update Product' : 'Create Product'}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
