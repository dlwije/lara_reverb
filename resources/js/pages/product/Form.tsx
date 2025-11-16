'use client';

import React, { useCallback, useEffect, useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Product } from '@/types/product';
import AutoComplete from '@/components/AutoComplete';

interface FormProps {
    current?: Product;
    categories: any[];
    brands: any[];
    units: any[];
    taxes: any[];
    stores: any[];
    custom_fields: any[];
}

// Form components
const FormSection: React.FC<{
    children: React.ReactNode;
    onSubmit?: (e: React.FormEvent) => void;
}> = ({ children, onSubmit }) => (
    <form onSubmit={onSubmit} className="space-y-6">
        {children}
    </form>
);

const Input: React.FC<{
    id: string;
    label: string;
    type?: string;
    value?: string | number;
    onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
    onKeyUp?: (e: React.KeyboardEvent<HTMLInputElement>) => void;
    error?: string;
    readonly?: boolean;
    min?: number;
    max?: number;
    placeholder?: string;
}> = ({ id, label, type = 'text', value, onChange, onKeyUp, error, readonly, min, max, placeholder }) => (
    <div>
        <label htmlFor={id} className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {label}
        </label>
        <input
            id={id}
            type={type}
            value={value || ''}
            onChange={onChange}
            onKeyUp={onKeyUp}
            readOnly={readonly}
            min={min}
            max={max}
            placeholder={placeholder}
            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
        />
        {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
);

const Textarea: React.FC<{
    id: string;
    label: string;
    value?: string;
    onChange?: (e: React.ChangeEvent<HTMLTextAreaElement>) => void;
    error?: string;
    rows?: number;
}> = ({ id, label, value, onChange, error, rows = 3 }) => (
    <div>
        <label htmlFor={id} className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {label}
        </label>
        <textarea
            id={id}
            rows={rows}
            value={value || ''}
            onChange={onChange}
            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
        />
        {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
);

const Toggle: React.FC<{
    id: string;
    label: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    text?: string;
}> = ({ id, label, checked, onChange, text }) => (
    <div className="flex items-center">
        <input
            id={id}
            type="checkbox"
            checked={checked}
            onChange={(e) => onChange(e.target.checked)}
            className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        />
        <label htmlFor={id} className="ml-2 block text-sm text-gray-900 dark:text-gray-100">
            {label} {text && <span className="text-gray-500">{text}</span>}
        </label>
    </div>
);

const Button: React.FC<{
    type?: 'button' | 'submit';
    onClick?: () => void;
    children: React.ReactNode;
    className?: string;
}> = ({ type = 'button', onClick, children, className = '' }) => (
    <button
        type={type}
        onClick={onClick}
        className={`inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ${className}`}
    >
        {children}
    </button>
);

const SecondaryButton: React.FC<{
    type?: 'button' | 'submit';
    onClick?: () => void;
    children: React.ReactNode;
    className?: string;
}> = ({ type = 'button', onClick, children, className = '' }) => (
    <button
        type={type}
        onClick={onClick}
        className={`inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 ${className}`}
    >
        {children}
    </button>
);

const FileInput: React.FC<{
    id: string;
    label: string;
    multiple?: boolean;
    onChange?: (files: FileList | null) => void;
    error?: string;
}> = ({ id, label, multiple, onChange, error }) => (
    <div>
        <label htmlFor={id} className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {label}
        </label>
        <input
            id={id}
            type="file"
            multiple={multiple}
            onChange={(e) => onChange?.(e.target.files)}
            className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-full file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
        />
        {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
);

const CustomFields: React.FC<{
    custom_fields: any[];
    errors: Record<string, string>;
    extra_attributes: Record<string, any>;
}> = ({ custom_fields, errors, extra_attributes }) => (
    <div>
        {/* Implement custom fields rendering based on your requirements */}
        <p className="text-sm text-gray-500">Custom fields component placeholder</p>
    </div>
);

const LoadingButton: React.FC<{
    type?: 'button' | 'submit';
    onClick?: () => void;
    loading?: boolean;
    children: React.ReactNode;
    className?: string;
}> = ({ type = 'button', onClick, loading, children, className = '' }) => (
    <button
        type={type}
        onClick={onClick}
        disabled={loading}
        className={`inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 ${className}`}
    >
        {loading && (
            <svg className="-ml-1 mr-3 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path
                    className="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>
        )}
        {children}
    </button>
);

const ActionMessage: React.FC<{
    on: boolean;
    children: React.ReactNode;
    className?: string;
}> = ({ on, children, className = '' }) => (
    <div className={`transition-opacity duration-300 ${on ? 'opacity-100' : 'opacity-0'} ${className}`}>{children}</div>
);

const Icon: React.FC<{ name: string; size?: string }> = ({ name, size = 'w-5 h-5' }) => (
    <span className={`inline-block ${size}`}>
        {/* Replace with your actual icon implementation */}
        {name === 'add' && '+'}
        {name === 'trash-o' && '🗑️'}
    </span>
);

const Link: React.FC<{ href: string; children: React.ReactNode; className?: string }> = ({ href, children, className }) => (
    <a href={href} className={className}>
        {children}
    </a>
);

export default function ProductForm({ current, categories = [], brands = [], units = [], taxes = [], stores = [], custom_fields = [] }: FormProps) {
    // Initialize form data
    const [data, setData] = useState<Product>({
        type: current?.type || 'Standard',
        name: current?.name || '',
        secondary_name: current?.secondary_name || '',
        code: current?.code || '',
        symbology: current?.symbology || '',
        category_id: current?.category_id || undefined,
        subcategory_id: current?.subcategory_id || undefined,
        brand_id: current?.brand_id || undefined,
        unit_id: current?.unit_id || undefined,
        sale_unit_id: current?.sale_unit_id || undefined,
        purchase_unit_id: current?.purchase_unit_id || undefined,
        cost: current?.cost || undefined,
        price: current?.price || undefined,
        min_price: current?.min_price || undefined,
        max_price: current?.max_price || undefined,
        max_discount: current?.max_discount || undefined,
        rack_location: current?.rack_location || '',
        weight: current?.weight || undefined,
        dimensions: current?.dimensions || '',
        hsn_number: current?.hsn_number || '',
        sac_number: current?.sac_number || '',
        supplier_id: current?.supplier_id || undefined,
        supplier_part_id: current?.supplier_part_id || '',
        alert_quantity: current?.alert_quantity || undefined,
        video_url: current?.video_url || '',
        details: current?.details || '',
        featured: current?.featured || false,
        hide_in_pos: current?.hide_in_pos || false,
        hide_in_shop: current?.hide_in_shop || false,
        tax_included: current?.tax_included || false,
        can_edit_price: current?.can_edit_price || false,
        has_expiry_date: current?.has_expiry_date || false,
        has_variants: current?.has_variants || false,
        dont_track_stock: current?.dont_track_stock || false,
        set_stock: current?.set_stock || false,
        has_serials: current?.has_serials || false,
        title: current?.title || '',
        description: current?.description || '',
        keywords: current?.keywords || '',
        noindex: current?.noindex || false,
        nofollow: current?.nofollow || false,
        products: current?.products || [],
        taxes: current?.taxes || [],
        unit_prices: current?.unit_prices || {},
        variants: current?.variants || [],
        variations: current?.variations || [],
        stores:
            current?.stores ||
            stores.map((store) => ({
                id: store.id,
                price: null,
                quantity: null,
                alert_quantity: null,
                taxes: [],
            })),
        serials: current?.serials || [],
        extra_attributes: current?.extra_attributes || {},
    });

    // State for additional functionality
    const [search, setSearch] = useState('');
    const [result, setResult] = useState<any[]>([]);
    const [category, setCategory] = useState<any>(null);
    const [unit, setUnit] = useState<any>(null);
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const photoInputRef = useRef<HTMLInputElement>(null);

    // Helper functions
    const t = (key: string, params?: Record<string, any>) => {
        // Replace with your actual translation function
        let translated = key;
        if (params) {
            Object.entries(params).forEach(([k, v]) => {
                translated = translated.replace(`{${k}}`, String(v));
            });
        }
        return translated;
    };

    const route = (name: string, params?: any) => {
        // Replace with your actual route helper
        return `/${name.replace('.', '/')}${params ? `/${params}` : ''}`;
    };

    // Form submission
    const handleSubmit = useCallback(
        (e?: React.FormEvent, redirect = false) => {
            e?.preventDefault();
            setProcessing(true);
            setErrors({});

            // Simulate form submission
            setTimeout(() => {
                console.log('Form submitted:', data);
                setProcessing(false);
                setRecentlySuccessful(true);

                // Hide success message after 2 seconds
                setTimeout(() => setRecentlySuccessful(false), 2000);

                if (redirect) {
                    // In a real app, this would redirect to the products index
                    console.log('Redirecting to products index...');
                }
            }, 1000);
        },
        [data],
    );

    // Product search for combo products
    const searchProducts = useCallback(async (query: string) => {
        if (!query) {
            setResult([]);
            return;
        }

        // Mock product search - replace with actual API call
        const mockProducts = [
            { id: 1, name: 'Product A', code: 'PA001' },
            { id: 2, name: 'Product B', code: 'PB002' },
            { id: 3, name: 'Product C', code: 'PC003' },
        ];

        const filtered = mockProducts.filter((p) => p.name.toLowerCase().includes(query.toLowerCase()));
        setResult(filtered);
    }, []);

    // Effect for product search
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (search) {
                searchProducts(search);
            }
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [search, searchProducts]);

    // Add product to combo
    const addProduct = useCallback(
        (product: any) => {
            const existingProducts = data.products || [];
            if (!existingProducts.find((p) => p.id === product.id)) {
                setData({ ...data, products: [...existingProducts, { ...product, quantity: 1 }] });
            }
            setSearch('');
            setResult([]);
        },
        [data],
    );

    // Remove product from combo
    const removeItem = useCallback(
        (product: any) => {
            const updatedProducts = (data.products || []).filter((p) => p.id !== product.id);
            setData({ ...data, products: updatedProducts });
        },
        [data],
    );

    // Quantity changed for combo products
    const quantityChanged = useCallback(
        (product: any) => {
            const updatedProducts = (data.products || []).map((p) => (p.id === product.id ? { ...p, quantity: product.quantity } : p));
            setData({ ...data, products: updatedProducts });
        },
        [data],
    );

    // Generate variations
    const generateVariations = useCallback(() => {
        const variants = data.variants.filter((v) => v.name && v.options.length > 0);
        if (variants.length === 0) return;

        const combinations: any[] = [];

        const generateCombinations = (index: number, current: any) => {
            if (index === variants.length) {
                combinations.push({ ...current });
                return;
            }

            const variant = variants[index];
            variant.options.forEach((option) => {
                if (option.trim()) {
                    generateCombinations(index + 1, {
                        ...current,
                        meta: { ...current.meta, [variant.name]: option },
                        code: '',
                        cost: data.cost || 0,
                        price: data.price || 0,
                        rack_location: '',
                        weight: 0,
                        dimensions: '',
                    });
                }
            });
        };

        generateCombinations(0, { meta: {} });
        setData({ ...data, variations: combinations });
    }, [data]);

    // Photo handling
    const selectNewPhoto = useCallback(() => {
        photoInputRef.current?.click();
    }, []);

    const updatePhotoPreview = useCallback(() => {
        const file = photoInputRef.current?.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                setPhotoPreview(e.target?.result as string);
            };
            reader.readAsDataURL(file);
            setData({ ...data, photo: file as any });
        }
    }, [data]);

    const deletePhoto = useCallback(() => {
        setPhotoPreview(null);
        setData({ ...data, photo: undefined });
        if (photoInputRef.current) {
            photoInputRef.current.value = '';
        }
    }, [data]);

    // Serial number helpers
    const countSerials = useCallback((start: string, end: string) => {
        if (!start) return 0;
        if (!end) return 1;
        const startNum = Number.parseInt(start);
        const endNum = Number.parseInt(end);
        return isNaN(startNum) || isNaN(endNum) ? 1 : Math.max(0, endNum - startNum + 1);
    }, []);

    const focusNextSerialInput = useCallback(
        (e: React.KeyboardEvent, index: number) => {
            const nextInput = document.getElementById(`serial_${index + 1}`);
            if (nextInput) {
                nextInput.focus();
            } else {
                // Add new serial if this is the last one
                setData({ ...data, serials: [...data.serials, { number: '', till: '' }] });
            }
        },
        [data],
    );

    const focusOnNextOption = useCallback(
        (e: React.KeyboardEvent, optionIndex: number, variantIndex: number) => {
            const nextInput = document.getElementById(`option_${variantIndex}_${optionIndex + 1}`);
            if (nextInput) {
                nextInput.focus();
            } else {
                // Add new option if this is the last one
                const updatedVariants = [...data.variants];
                updatedVariants[variantIndex].options.push('');
                setData({ ...data, variants: updatedVariants });
            }
        },
        [data],
    );

    return (
        <AppLayout>
            <div className="px-0 pb-0 pt-6 sm:px-6 sm:py-8 lg:px-8">
                <FormSection onSubmit={handleSubmit}>
                    {/* Header */}
                    <div className="mb-8">
                        <div className="block w-full sm:flex sm:items-start sm:justify-between lg:block">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {current?.id ? t('Edit {x}', { x: t('Product') }) : t('Add {x}', { x: t('Product') })}
                                </h1>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    {t('Please fill the form below to {action} {record}.', {
                                        record: t('product'),
                                        action: current?.id ? t('edit') : t('add'),
                                    })}
                                </p>
                            </div>
                            <div className="me-3 mt-6 sm:mt-0 lg:mt-6">
                                <Link href={route('products.index')} className="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                    {t('List {x}', { x: t('Products') })}
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Form Grid */}
                    <div className="grid grid-cols-6 gap-6">
                        {/* Type */}
                        <div className="col-span-6 sm:col-span-3">
                            <AutoComplete
                                id="type"
                                label={t('Type')}
                                value={data.type}
                                onChange={(value) => setData({ ...data, type: value })}
                                error={errors.type}
                                suggestions={[
                                    { value: 'Standard', label: t('Standard') },
                                    { value: 'Service', label: t('Service') },
                                    { value: 'Digital', label: t('Digital') },
                                    { value: 'Combo', label: t('Combo') },
                                    { value: 'Recipe', label: t('Recipe') },
                                ]}
                            />
                        </div>
                        <div className="col-span-6 sm:col-span-3"></div>

                        {/* Combo Products Section */}
                        {data.type === 'Combo' && (
                            <div className="col-span-full mb-6 rounded-sm border border-gray-200 p-6 dark:border-gray-700">
                                <div className="relative">
                                    <Input
                                        id="product-search"
                                        label=""
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder={t('Scan barcode or type to search')}
                                    />
                                    {search && result && result.length > 0 && (
                                        <div className="absolute left-0 right-0 top-full z-10 mt-2 rounded-md bg-white py-1 ring-1 dark:bg-gray-700 dark:ring-gray-700">
                                            {result.map((p) => (
                                                <button
                                                    key={p.id}
                                                    type="button"
                                                    onClick={() => addProduct(p)}
                                                    className="w-full px-4 py-1.5 text-left hover:bg-gray-100 dark:hover:bg-gray-900"
                                                >
                                                    {p.name}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                {data.products && data.products.length > 0 && (
                                    <div>
                                        <h4 className="mb-3 mt-6 text-lg font-bold">{t('Combo Products')}</h4>
                                        <table className="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <tbody>
                                                {data.products.map((product, index) => (
                                                    <tr key={product.code}>
                                                        <td className="w-8 py-2">{index + 1}.</td>
                                                        <td className="p-2">{product.name}</td>
                                                        <td className="w-36 py-2">
                                                            <Input
                                                                id={`quantity-${index}`}
                                                                label=""
                                                                type="number"
                                                                min={0}
                                                                value={product.quantity}
                                                                onChange={(e) => {
                                                                    const updatedProduct = {
                                                                        ...product,
                                                                        quantity: Number.parseInt(e.target.value) || 0,
                                                                    };
                                                                    quantityChanged(updatedProduct);
                                                                }}
                                                            />
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Name */}
                        <div className="col-span-6 sm:col-span-3">
                            <Input
                                id="name"
                                label={t('Name')}
                                value={data.name}
                                onChange={(e) => setData({ ...data, name: e.target.value })}
                                error={errors.name}
                            />
                        </div>

                        {/* Secondary Name */}
                        <div className="col-span-6 sm:col-span-3">
                            <Input
                                id="secondary_name"
                                label={t('Secondary Name')}
                                value={data.secondary_name}
                                onChange={(e) => setData({ ...data, secondary_name: e.target.value })}
                                error={errors.secondary_name}
                            />
                        </div>

                        {/* Code */}
                        <div className="col-span-6 sm:col-span-3">
                            <Input
                                id="code"
                                label={`${t('Code')} (${t('barcode')})`}
                                value={data.code}
                                onChange={(e) => setData({ ...data, code: e.target.value })}
                                error={errors.code}
                            />
                        </div>

                        {/* Symbology */}
                        <div className="col-span-6 sm:col-span-3">
                            <AutoComplete
                                id="symbology"
                                label={t('Symbology')}
                                value={data.symbology}
                                onChange={(value) => setData({ ...data, symbology: value })}
                                error={errors.symbology}
                                suggestions={['CODE128', 'CODE39', 'EAN8', 'EAN13', 'UPC']}
                            />
                        </div>

                        {/* Category */}
                        <div className="col-span-6 sm:col-span-3">
                            <AutoComplete
                                id="category_id"
                                label={t('Category')}
                                value={data.category_id}
                                onChange={(value) => {
                                    setData({ ...data, category_id: value });
                                    const selectedCategory = categories.find((c) => c.id === value);
                                    setCategory(selectedCategory);
                                }}
                                error={errors.category_id}
                                suggestions={categories}
                                valueKey="id"
                                labelKey="name"
                            />
                        </div>

                        {/* Subcategory */}
                        <div className="col-span-6 sm:col-span-3">
                            <AutoComplete
                                id="subcategory_id"
                                label={t('Subcategory')}
                                value={data.subcategory_id}
                                onChange={(value) => setData({ ...data, subcategory_id: value })}
                                error={errors.subcategory_id}
                                suggestions={category?.children || []}
                                valueKey="id"
                                labelKey="name"
                            />
                        </div>

                        {/* Brand - only for Standard products */}
                        {data.type === 'Standard' && (
                            <div className="col-span-6 sm:col-span-3">
                                <AutoComplete
                                    id="brand_id"
                                    label={t('Brand')}
                                    value={data.brand_id}
                                    onChange={(value) => setData({ ...data, brand_id: value })}
                                    error={errors.brand_id}
                                    suggestions={brands}
                                    valueKey="id"
                                    labelKey="name"
                                    clearable
                                />
                            </div>
                        )}

                        {/* Unit fields for Standard products */}
                        {data.type === 'Standard' && (
                            <>
                                <div className="col-span-6 sm:col-span-3">
                                    <AutoComplete
                                        id="unit_id"
                                        label={t('Unit')}
                                        value={data.unit_id}
                                        onChange={(value) => {
                                            setData({ ...data, unit_id: value });
                                            const selectedUnit = units.find((u) => u.id === value);
                                            setUnit(selectedUnit);
                                            if (selectedUnit?.subunits) {
                                                const unitPrices = selectedUnit.subunits.reduce((acc: any, subunit: any) => {
                                                    acc[subunit.id] = { cost: null, price: null };
                                                    return acc;
                                                }, {});
                                                setData({ ...data, unit_prices: unitPrices });
                                            }
                                        }}
                                        error={errors.unit_id}
                                        suggestions={units}
                                        valueKey="id"
                                        labelKey="name"
                                        clearable
                                    />
                                </div>

                                {/* Sale Unit and Purchase Unit */}
                                {unit?.subunits && unit.subunits.length > 0 && (
                                    <>
                                        <div className="col-span-6 sm:col-span-3">
                                            <AutoComplete
                                                id="sale_unit_id"
                                                label={t('Sale Unit')}
                                                value={data.sale_unit_id}
                                                onChange={(value) => setData({ ...data, sale_unit_id: value })}
                                                error={errors.sale_unit_id}
                                                suggestions={unit.subunits}
                                                valueKey="id"
                                                labelKey="name"
                                                clearable
                                            />
                                        </div>
                                        <div className="col-span-6 sm:col-span-3">
                                            <AutoComplete
                                                id="purchase_unit_id"
                                                label={t('Purchase Unit')}
                                                value={data.purchase_unit_id}
                                                onChange={(value) => setData({ ...data, purchase_unit_id: value })}
                                                error={errors.purchase_unit_id}
                                                suggestions={unit.subunits}
                                                valueKey="id"
                                                labelKey="name"
                                                clearable
                                            />
                                        </div>
                                    </>
                                )}
                            </>
                        )}

                        {/* Cost */}
                        <div className="col-span-6 sm:col-span-3">
                            <Input
                                id="cost"
                                type="number"
                                label={t('Purchase Cost')}
                                value={data.cost}
                                onChange={(e) => setData({ ...data, cost: Number.parseFloat(e.target.value) || undefined })}
                                error={errors.cost}
                                readonly={['Combo', 'Recipe'].includes(data.type)}
                            />
                        </div>

                        {/* Price */}
                        <div className="col-span-6 sm:col-span-3">
                            <Input
                                id="price"
                                type="number"
                                label={t('Selling Price')}
                                value={data.price}
                                onChange={(e) => setData({ ...data, price: Number.parseFloat(e.target.value) || undefined })}
                                error={errors.price}
                            />
                        </div>

                        {/* Min Price */}
                        <div className="col-span-6 sm:col-span-3">
                            <Input
                                id="min_price"
                                type="number"
                                label={t('Minimum Price')}
                                value={data.min_price}
                                onChange={(e) => setData({ ...data, min_price: Number.parseFloat(e.target.value) || undefined })}
                                error={errors.min_price}
                            />
                        </div>

                        {/* Max Price */}
                        <div className="col-span-6 sm:col-span-3">
                            <Input
                                id="max_price"
                                type="number"
                                label={t('Maximum Price')}
                                value={data.max_price}
                                onChange={(e) => setData({ ...data, max_price: Number.parseFloat(e.target.value) || undefined })}
                                error={errors.max_price}
                            />
                        </div>

                        {/* Unit Prices for subunits */}
                        {!['Combo', 'Recipe'].includes(data.type) && data.unit_prices && unit?.subunits?.length > 0 && (
                            <>
                                {unit.subunits.map((subunit: any) => (
                                    <div
                                        key={subunit.id}
                                        className="relative col-span-full mt-3 grid grid-cols-6 gap-6 rounded-md border border-gray-200 px-4 pb-4 pt-8 dark:border-gray-700"
                                    >
                                        <div className="absolute -top-4 left-4 flex items-center gap-x-4 rounded-md border border-gray-200 bg-gray-100 px-3 py-0.5 text-lg font-extrabold dark:border-gray-700 dark:bg-gray-800">
                                            {subunit.name}
                                        </div>
                                        <div className="col-span-6 sm:col-span-3">
                                            <Input
                                                id={`unit_cost_${subunit.id}`}
                                                type="number"
                                                label={t('Purchase Cost')}
                                                value={data.unit_prices[subunit.id]?.cost}
                                                onChange={(e) => {
                                                    const updatedUnitPrices = { ...data.unit_prices };
                                                    updatedUnitPrices[subunit.id] = {
                                                        ...updatedUnitPrices[subunit.id],
                                                        cost: Number.parseFloat(e.target.value) || null,
                                                    };
                                                    setData({ ...data, unit_prices: updatedUnitPrices });
                                                }}
                                                error={errors.unit_prices?.[subunit.id]?.cost}
                                            />
                                        </div>
                                        <div className="col-span-6 sm:col-span-3">
                                            <Input
                                                id={`unit_price_${subunit.id}`}
                                                type="number"
                                                label={t('Selling Price')}
                                                value={data.unit_prices[subunit.id]?.price}
                                                onChange={(e) => {
                                                    const updatedUnitPrices = { ...data.unit_prices };
                                                    updatedUnitPrices[subunit.id] = {
                                                        ...updatedUnitPrices[subunit.id],
                                                        price: Number.parseFloat(e.target.value) || null,
                                                    };
                                                    setData({ ...data, unit_prices: updatedUnitPrices });
                                                }}
                                                error={errors.unit_prices?.[subunit.id]?.price}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </>
                        )}

                        {/* Taxes */}
                        <div className="col-span-full">
                            <AutoComplete
                                id="taxes"
                                label={t('Taxes')}
                                value={data.taxes}
                                onChange={(value) => setData({ ...data, taxes: value })}
                                error={errors.taxes}
                                suggestions={taxes}
                                valueKey="id"
                                labelKey="name"
                                multiple
                            />
                        </div>

                        {/* Maximum Discount */}
                        <div className="col-span-6 sm:col-span-3">
                            <Input
                                id="max_discount"
                                type="number"
                                label={t('Maximum Discount')}
                                value={data.max_discount}
                                onChange={(e) => setData({ ...data, max_discount: Number.parseFloat(e.target.value) || undefined })}
                                error={errors.max_discount}
                            />
                        </div>

                        {/* Rack Location */}
                        {['Standard', 'Combo', 'Recipe'].includes(data.type) && (
                            <div className="col-span-6 sm:col-span-3">
                                <Input
                                    id="rack_location"
                                    label={t('Rack Location')}
                                    value={data.rack_location}
                                    onChange={(e) => setData({ ...data, rack_location: e.target.value })}
                                    error={errors.rack_location}
                                />
                            </div>
                        )}

                        {/* Weight */}
                        {['Standard', 'Combo', 'Recipe'].includes(data.type) && (
                            <div className="col-span-6 sm:col-span-3">
                                <Input
                                    id="weight"
                                    type="number"
                                    label={t('Weight')}
                                    value={data.weight}
                                    onChange={(e) => setData({ ...data, weight: Number.parseFloat(e.target.value) || undefined })}
                                    error={errors.weight}
                                />
                            </div>
                        )}

                        {/* Dimensions */}
                        {['Standard', 'Combo', 'Recipe'].includes(data.type) && (
                            <div className="col-span-6 sm:col-span-3">
                                <Input
                                    id="dimensions"
                                    label={t('Dimensions')}
                                    value={data.dimensions}
                                    onChange={(e) => setData({ ...data, dimensions: e.target.value })}
                                    error={errors.dimensions}
                                />
                            </div>
                        )}

                        {/* HSN Number */}
                        {['Standard', 'Combo', 'Recipe'].includes(data.type) && (
                            <div className="col-span-6 sm:col-span-3">
                                <Input
                                    id="hsn_number"
                                    label={t('HSN Number')}
                                    value={data.hsn_number}
                                    onChange={(e) => setData({ ...data, hsn_number: e.target.value })}
                                    error={errors.hsn_number}
                                />
                            </div>
                        )}

                        {/* SAC Number */}
                        {data.type === 'Service' && (
                            <div className="col-span-6 sm:col-span-3">
                                <Input
                                    id="sac_number"
                                    label={t('SAC Number')}
                                    value={data.sac_number}
                                    onChange={(e) => setData({ ...data, sac_number: e.target.value })}
                                    error={errors.sac_number}
                                />
                            </div>
                        )}

                        {/* Supplier */}
                        {data.type === 'Standard' && (
                            <div className="col-span-6 sm:col-span-3">
                                <AutoComplete
                                    id="supplier_id"
                                    label={t('Supplier')}
                                    value={data.supplier_id}
                                    onChange={(value) => setData({ ...data, supplier_id: value })}
                                    error={errors.supplier_id}
                                    suggestions={route('search.suppliers')}
                                    valueKey="id"
                                    labelKey="company"
                                />
                            </div>
                        )}

                        {/* Supplier Part Id */}
                        {data.type === 'Standard' && (
                            <div className="col-span-6 sm:col-span-3">
                                <Input
                                    id="supplier_part_id"
                                    label={t('Supplier Part Id')}
                                    value={data.supplier_part_id}
                                    onChange={(e) => setData({ ...data, supplier_part_id: e.target.value })}
                                    error={errors.supplier_part_id}
                                />
                            </div>
                        )}

                        {/* Alert Quantity */}
                        {data.type === 'Standard' && (
                            <div className="col-span-6 sm:col-span-3">
                                <Input
                                    id="alert_quantity"
                                    type="number"
                                    label={t('Alert (Low Stock) Quantity')}
                                    value={data.alert_quantity}
                                    onChange={(e) => setData({ ...data, alert_quantity: Number.parseInt(e.target.value) || undefined })}
                                    error={errors.alert_quantity}
                                />
                            </div>
                        )}

                        {/* Custom Fields */}
                        <div className="col-span-full">
                            <CustomFields custom_fields={custom_fields} errors={errors} extra_attributes={data.extra_attributes || {}} />
                        </div>

                        {/* File for Digital products */}
                        {data.type === 'Digital' && (
                            <div className="col-span-full">
                                <FileInput
                                    id="file"
                                    multiple
                                    label={t('File')}
                                    onChange={(files) => setData({ ...data, file: files ? Array.from(files) : undefined })}
                                    error={errors.file}
                                />
                            </div>
                        )}

                        {/* Photo */}
                        <div className="col-span-full">
                            <input id="photo" ref={photoInputRef} type="file" className="hidden" onChange={updatePhotoPreview} />

                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{t('Photo')}</label>

                            {/* Current Photo */}
                            {!photoPreview && current?.photo && (
                                <div className="mt-2 rounded-md p-1">
                                    <img
                                        alt={t('Photo')}
                                        src={current.photo || '/placeholder.svg'}
                                        className="h-full max-h-40 min-h-20 w-full max-w-64 rounded-md object-contain"
                                    />
                                </div>
                            )}

                            {/* New Photo Preview */}
                            {photoPreview && (
                                <div className="mt-2 rounded-md p-1">
                                    <div
                                        className="block h-full max-h-40 min-h-24 w-full max-w-64 rounded-md bg-contain bg-no-repeat"
                                        style={{ backgroundImage: `url('${photoPreview}')` }}
                                    />
                                </div>
                            )}

                            <SecondaryButton className="me-2 mt-2" type="button" onClick={selectNewPhoto}>
                                {t('Select A New Photo')}
                            </SecondaryButton>

                            {current?.photo && (
                                <SecondaryButton
                                    type="button"
                                    onClick={deletePhoto}
                                    className="mt-2 flex items-center justify-center rounded-md bg-gray-50 p-1"
                                >
                                    {t('Remove Photo')}
                                </SecondaryButton>
                            )}

                            {errors.photo && <p className="mt-2 text-sm text-red-600">{errors.photo}</p>}
                        </div>

                        {/* Photos */}
                        <div className="col-span-full">
                            <FileInput
                                id="photos"
                                multiple
                                label={t('Photos')}
                                onChange={(files) => setData({ ...data, photos: files ? Array.from(files) : undefined })}
                                error={errors.photos}
                            />
                        </div>

                        {/* Video URL */}
                        <div className="col-span-full">
                            <Input
                                id="video_url"
                                label={t('Video URL')}
                                value={data.video_url}
                                onChange={(e) => setData({ ...data, video_url: e.target.value })}
                                error={errors.video_url}
                            />
                        </div>

                        {/* Details */}
                        <div className="col-span-full">
                            <Textarea
                                id="details"
                                rows={5}
                                label={t('Product Details')}
                                value={data.details}
                                onChange={(e) => setData({ ...data, details: e.target.value })}
                                error={errors.details}
                            />
                        </div>

                        {/* Toggle Options */}
                        <div className="col-span-full flex flex-col gap-2 overflow-x-auto rounded-md border border-gray-200 p-4 dark:border-gray-700">
                            <Toggle
                                id="featured"
                                label={t('Featured')}
                                checked={data.featured}
                                onChange={(checked) => setData({ ...data, featured: checked })}
                            />
                            <Toggle
                                id="hide_in_pos"
                                label={t('Hide in POS')}
                                checked={data.hide_in_pos}
                                onChange={(checked) => setData({ ...data, hide_in_pos: checked })}
                            />
                            <Toggle
                                id="hide_in_shop"
                                label={t('Hide in Shop')}
                                checked={data.hide_in_shop}
                                onChange={(checked) => setData({ ...data, hide_in_shop: checked })}
                            />
                            <Toggle
                                id="tax_included"
                                label={t('Tax is included in price')}
                                checked={data.tax_included}
                                onChange={(checked) => setData({ ...data, tax_included: checked })}
                            />
                            <Toggle
                                id="can_edit_price"
                                label={t('Allow to change price while selling')}
                                checked={data.can_edit_price}
                                onChange={(checked) => setData({ ...data, can_edit_price: checked })}
                            />
                            <Toggle
                                id="has_expiry_date"
                                label={t('This product has expiry date')}
                                text={`(${t('show expiry date input while purchasing')})`}
                                checked={data.has_expiry_date}
                                onChange={(checked) => setData({ ...data, has_expiry_date: checked })}
                            />
                        </div>

                        {/* Variants Section for Standard products */}
                        {data.type === 'Standard' && (
                            <div className="col-span-full flex flex-col gap-2 overflow-x-auto rounded-md border border-gray-200 p-4 dark:border-gray-700">
                                <Toggle
                                    id="has_variants"
                                    label={t('This product has variants')}
                                    checked={data.has_variants}
                                    onChange={(checked) => setData({ ...data, has_variants: checked })}
                                />

                                {data.has_variants && (
                                    <div className="col-span-full">
                                        <div className="grid grid-cols-6 gap-6">
                                            <div className="col-span-full mt-6 flex items-center justify-between">
                                                <h4 className="text-lg font-bold">{t('Variants')}</h4>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        if (data.variants.length < 3) {
                                                            setData({ ...data, variants: [...data.variants, { name: '', options: [''] }] });
                                                        } else {
                                                            alert(t('You have already added maximum number of variants.'));
                                                        }
                                                    }}
                                                    className="relative -ml-px inline-flex items-center rounded-md bg-white px-2 py-2 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 dark:bg-gray-900 dark:ring-gray-700 dark:hover:bg-gray-950"
                                                >
                                                    <Icon name="add" size="w-5 h-5 sm:mr-2" />
                                                    <span className="hidden sm:block">{t('Add {x}', { x: t('Variant') })}</span>
                                                </button>
                                            </div>

                                            {data.variants.map((variant, index) => (
                                                <div
                                                    key={`variant_${index}`}
                                                    className="relative col-span-full mt-2 grid grid-cols-6 gap-6 rounded-md border border-gray-200 px-6 pb-6 pt-10 dark:border-gray-700"
                                                >
                                                    <div className="absolute -top-4 left-4 flex items-center gap-x-4 rounded-md border border-gray-200 bg-gray-100 px-3 py-0.5 text-lg font-extrabold dark:border-gray-700 dark:bg-gray-800">
                                                        {t('Variant {x}', { x: index + 1 })}
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                const updatedVariants = data.variants.filter((_, i) => i !== index);
                                                                setData({ ...data, variants: updatedVariants });
                                                            }}
                                                            className="relative -mr-2.5 inline-flex items-center rounded-md p-1 text-gray-500 hover:bg-red-100 hover:text-red-700 focus:z-10 dark:hover:bg-red-950 dark:hover:text-red-200"
                                                        >
                                                            <span className="sr-only">{t('Remove')}</span>
                                                            <Icon name="trash-o" size="w-5 h-5" />
                                                        </button>
                                                    </div>
                                                    <div className="col-span-full flex items-end gap-6">
                                                        <div className="flex-1">
                                                            <Input
                                                                label={t('Name')}
                                                                value={variant.name}
                                                                onChange={(e) => {
                                                                    const updatedVariants = [...data.variants];
                                                                    updatedVariants[index].name = e.target.value;
                                                                    setData({ ...data, variants: updatedVariants });
                                                                }}
                                                            />
                                                        </div>
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                const updatedVariants = [...data.variants];
                                                                updatedVariants[index].options.push('');
                                                                setData({ ...data, variants: updatedVariants });
                                                            }}
                                                            className="relative inline-flex items-center rounded-md bg-white p-2.5 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 dark:bg-gray-900 dark:ring-gray-700 dark:hover:bg-gray-950"
                                                        >
                                                            <Icon name="add" size="w-5 h-5 sm:mr-2" />
                                                            <span className="hidden sm:block">{t('Add {x}', { x: t('Option') })}</span>
                                                        </button>
                                                    </div>
                                                    {variant.options.map((option, oi) => (
                                                        <div key={`oc_${index}_${oi}`} className="col-span-6 sm:col-span-3">
                                                            <Input
                                                                id={`option_${index}_${oi}`}
                                                                label={t('Option {x}', { x: oi + 1 })}
                                                                value={option}
                                                                onChange={(e) => {
                                                                    const updatedVariants = [...data.variants];
                                                                    updatedVariants[index].options[oi] = e.target.value;
                                                                    setData({ ...data, variants: updatedVariants });
                                                                }}
                                                                onKeyUp={(e) => {
                                                                    if (e.key === 'Enter') {
                                                                        focusOnNextOption(e, oi, index);
                                                                    }
                                                                }}
                                                            />
                                                        </div>
                                                    ))}
                                                </div>
                                            ))}

                                            <div className="col-span-full">
                                                <Button type="button" onClick={generateVariations}>
                                                    {t('Generate Variations')}
                                                </Button>
                                            </div>

                                            {data.variations.length > 0 && (
                                                <>
                                                    <div className="col-span-full -mb-6">
                                                        <h4 className="text-lg font-bold">{t('All Variations')}</h4>
                                                    </div>
                                                    <div className="col-span-full overflow-x-auto border-l border-gray-200 dark:border-gray-700">
                                                        <div className="-ml-px inline-block min-w-full align-middle dark:border-gray-700">
                                                            <table className="w-full border-separate border-spacing-0">
                                                                <thead>
                                                                    <tr className="bg-gray-50 dark:bg-gray-800">
                                                                        {data.variants.map((va, vi) => (
                                                                            <th
                                                                                key={`option${vi}`}
                                                                                className="border-y border-l border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700"
                                                                            >
                                                                                {va.name}
                                                                            </th>
                                                                        ))}
                                                                        <th className="border-y border-l border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700">
                                                                            {t('Code')}
                                                                        </th>
                                                                        <th className="border-y border-l border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700">
                                                                            {t('Cost')}
                                                                        </th>
                                                                        <th className="border-y border-l border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700">
                                                                            {t('Price')}
                                                                        </th>
                                                                        <th className="border-y border-l border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700">
                                                                            {t('Rack')}
                                                                        </th>
                                                                        <th className="border-y border-l border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700">
                                                                            {t('Weight')}
                                                                        </th>
                                                                        <th className="border-y border-l border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700">
                                                                            {t('Dimensions')}
                                                                        </th>
                                                                        <th className="border border-gray-200 px-3 py-2 dark:border-gray-700"></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    {data.variations.map((variation, index) => (
                                                                        <tr
                                                                            key={`variation_${index}`}
                                                                            className="hover:bg-indigo-50 dark:hover:bg-indigo-950"
                                                                        >
                                                                            {data.variants.map((va, vi) => (
                                                                                <td
                                                                                    key={`option${vi}`}
                                                                                    className="border-b border-l border-gray-200 px-3 py-2 dark:border-gray-700"
                                                                                >
                                                                                    {variation.meta?.[va.name]}
                                                                                </td>
                                                                            ))}
                                                                            <td className="border-b border-l border-gray-200 px-3 py-2 dark:border-gray-700">
                                                                                <input
                                                                                    value={variation.code || ''}
                                                                                    onChange={(e) => {
                                                                                        const updatedVariations = [...data.variations];
                                                                                        updatedVariations[index].code = e.target.value;
                                                                                        setData({ ...data, variations: updatedVariations });
                                                                                    }}
                                                                                    className="w-24 border-0 bg-transparent px-2 py-0 focus:ring-0"
                                                                                />
                                                                            </td>
                                                                            <td className="border-b border-l border-gray-200 px-3 py-2 dark:border-gray-700">
                                                                                <input
                                                                                    type="number"
                                                                                    value={variation.cost || ''}
                                                                                    onChange={(e) => {
                                                                                        const updatedVariations = [...data.variations];
                                                                                        updatedVariations[index].cost =
                                                                                            Number.parseFloat(e.target.value) || 0;
                                                                                        setData({ ...data, variations: updatedVariations });
                                                                                    }}
                                                                                    className="w-24 border-0 bg-transparent py-0 pl-2 pr-0 focus:ring-0"
                                                                                />
                                                                            </td>
                                                                            <td className="border-b border-l border-gray-200 px-3 py-2 dark:border-gray-700">
                                                                                <input
                                                                                    type="number"
                                                                                    value={variation.price || ''}
                                                                                    onChange={(e) => {
                                                                                        const updatedVariations = [...data.variations];
                                                                                        updatedVariations[index].price =
                                                                                            Number.parseFloat(e.target.value) || 0;
                                                                                        setData({ ...data, variations: updatedVariations });
                                                                                    }}
                                                                                    className="w-24 border-0 bg-transparent py-0 pl-2 pr-0 focus:ring-0"
                                                                                />
                                                                            </td>
                                                                            <td className="border-b border-l border-gray-200 px-3 py-2 dark:border-gray-700">
                                                                                <input
                                                                                    value={variation.rack_location || ''}
                                                                                    onChange={(e) => {
                                                                                        const updatedVariations = [...data.variations];
                                                                                        updatedVariations[index].rack_location = e.target.value;
                                                                                        setData({ ...data, variations: updatedVariations });
                                                                                    }}
                                                                                    className="w-24 border-0 bg-transparent px-2 py-0 focus:ring-0"
                                                                                />
                                                                            </td>
                                                                            <td className="border-b border-l border-gray-200 px-3 py-2 dark:border-gray-700">
                                                                                <input
                                                                                    type="number"
                                                                                    value={variation.weight || ''}
                                                                                    onChange={(e) => {
                                                                                        const updatedVariations = [...data.variations];
                                                                                        updatedVariations[index].weight =
                                                                                            Number.parseFloat(e.target.value) || 0;
                                                                                        setData({ ...data, variations: updatedVariations });
                                                                                    }}
                                                                                    className="w-24 border-0 bg-transparent py-0 pl-2 pr-0 focus:ring-0"
                                                                                />
                                                                            </td>
                                                                            <td className="border-b border-l border-gray-200 px-3 py-2 dark:border-gray-700">
                                                                                <input
                                                                                    value={variation.dimensions || ''}
                                                                                    onChange={(e) => {
                                                                                        const updatedVariations = [...data.variations];
                                                                                        updatedVariations[index].dimensions = e.target.value;
                                                                                        setData({ ...data, variations: updatedVariations });
                                                                                    }}
                                                                                    placeholder={t('L x W x H')}
                                                                                    className="w-24 border-0 bg-transparent px-2 py-0 focus:ring-0"
                                                                                />
                                                                            </td>
                                                                            <td className="border-b border-l border-r border-gray-200 px-3 py-2 text-center dark:border-gray-700">
                                                                                <button
                                                                                    type="button"
                                                                                    onClick={() => {
                                                                                        const updatedVariations = data.variations.filter(
                                                                                            (_, i) => i !== index,
                                                                                        );
                                                                                        setData({ ...data, variations: updatedVariations });
                                                                                    }}
                                                                                    className="relative -ml-3 inline-flex items-center rounded-md p-1 text-gray-500 hover:bg-red-100 hover:text-red-700 focus:z-10 dark:hover:bg-red-950 dark:hover:text-red-200"
                                                                                >
                                                                                    <span className="sr-only">{t('Remove')}</span>
                                                                                    <Icon name="trash-o" size="w-5 h-5" />
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    ))}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Stock Management for Standard products */}
                        {data.type === 'Standard' && (
                            <div className="col-span-full flex flex-col gap-2 overflow-x-auto rounded-md border border-gray-200 p-4 dark:border-gray-700">
                                <Toggle
                                    id="dont_track_stock"
                                    label={t('Do not track stock for this product')}
                                    checked={data.dont_track_stock}
                                    onChange={(checked) => setData({ ...data, dont_track_stock: checked })}
                                />

                                {!data.dont_track_stock && !data.has_variants && (
                                    <>
                                        <Toggle
                                            id="set_stock"
                                            label={t('Set different price, quantity & taxes per stores')}
                                            checked={data.set_stock}
                                            onChange={(checked) => setData({ ...data, set_stock: checked })}
                                        />

                                        {data.set_stock && (
                                            <div className="col-span-full mt-8 flex flex-col gap-8">
                                                {stores.map((store, index) => (
                                                    <div
                                                        key={store.id}
                                                        className="relative grid grid-cols-6 gap-6 rounded-md border border-gray-200 px-6 pb-6 pt-10 dark:border-gray-700"
                                                    >
                                                        <div className="absolute -top-4 left-4 rounded-md border border-gray-200 bg-gray-100 px-3 py-0.5 text-lg font-extrabold dark:border-gray-700 dark:bg-gray-800">
                                                            {store.name}
                                                        </div>
                                                        <div className="col-span-6 sm:col-span-2">
                                                            <Input
                                                                type="number"
                                                                label={t('Selling Price')}
                                                                value={data.stores[index]?.price}
                                                                onChange={(e) => {
                                                                    const updatedStores = [...data.stores];
                                                                    updatedStores[index] = {
                                                                        ...updatedStores[index],
                                                                        price: Number.parseFloat(e.target.value) || null,
                                                                    };
                                                                    setData({ ...data, stores: updatedStores });
                                                                }}
                                                                error={errors[`stores.${index}.price`]}
                                                            />
                                                        </div>
                                                        {data.type === 'Standard' && (
                                                            <>
                                                                <div className="col-span-6 sm:col-span-2">
                                                                    <Input
                                                                        type="number"
                                                                        label={t('Quantity')}
                                                                        value={data.stores[index]?.quantity}
                                                                        onChange={(e) => {
                                                                            const updatedStores = [...data.stores];
                                                                            updatedStores[index] = {
                                                                                ...updatedStores[index],
                                                                                quantity: Number.parseInt(e.target.value) || null,
                                                                            };
                                                                            setData({ ...data, stores: updatedStores });
                                                                        }}
                                                                        error={errors[`stores.${index}.quantity`]}
                                                                    />
                                                                </div>
                                                                <div className="col-span-6 sm:col-span-2">
                                                                    <Input
                                                                        type="number"
                                                                        label={t('Alert (Low Stock) Quantity')}
                                                                        value={data.stores[index]?.alert_quantity}
                                                                        onChange={(e) => {
                                                                            const updatedStores = [...data.stores];
                                                                            updatedStores[index] = {
                                                                                ...updatedStores[index],
                                                                                alert_quantity: Number.parseInt(e.target.value) || null,
                                                                            };
                                                                            setData({ ...data, stores: updatedStores });
                                                                        }}
                                                                        error={errors[`stores.${index}.alert_quantity`]}
                                                                    />
                                                                </div>
                                                            </>
                                                        )}
                                                        <div className="col-span-full">
                                                            <AutoComplete
                                                                id={`store_taxes_${index}`}
                                                                label={t('Taxes')}
                                                                value={data.stores[index]?.taxes}
                                                                onChange={(value) => {
                                                                    const updatedStores = [...data.stores];
                                                                    updatedStores[index] = {
                                                                        ...updatedStores[index],
                                                                        taxes: value,
                                                                    };
                                                                    setData({ ...data, stores: updatedStores });
                                                                }}
                                                                error={errors[`stores.${index}.taxes`]}
                                                                suggestions={taxes}
                                                                valueKey="id"
                                                                labelKey="name"
                                                                multiple
                                                            />
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>
                        )}

                        {/* Serial Numbers */}
                        {!data.dont_track_stock && (
                            <div className="col-span-full flex flex-col gap-2 overflow-x-auto rounded-md border border-gray-200 p-4 dark:border-gray-700">
                                <Toggle
                                    id="has_serials"
                                    label={t('This product has serial numbers')}
                                    checked={data.has_serials}
                                    onChange={(checked) => setData({ ...data, has_serials: checked })}
                                />

                                {data.has_serials && (
                                    <div className="col-span-full">
                                        <div className="grid grid-cols-6 gap-6">
                                            <div className="col-span-full mt-6 flex items-center justify-between">
                                                <h4 className="text-lg font-bold">{t('Serial Numbers')}</h4>
                                                <button
                                                    type="button"
                                                    onClick={() => setData({ ...data, serials: [...data.serials, { number: '', till: '' }] })}
                                                    className="relative -ml-px inline-flex items-center rounded-md bg-white px-2 py-2 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 dark:bg-gray-900 dark:ring-gray-700 dark:hover:bg-gray-950"
                                                >
                                                    <Icon name="add" size="w-5 h-5 sm:mr-2" />
                                                    <span className="hidden sm:block">{t('Add {x}', { x: t('Serial') })}</span>
                                                </button>
                                            </div>

                                            {data.serials.map((serial, index) => (
                                                <React.Fragment key={`serial_${index}`}>
                                                    <div className="col-span-6 sm:col-span-3">
                                                        <Input
                                                            id={`serial_${index}`}
                                                            label={t('Serial Number')}
                                                            value={serial.number}
                                                            onChange={(e) => {
                                                                const updatedSerials = [...data.serials];
                                                                updatedSerials[index].number = e.target.value;
                                                                setData({ ...data, serials: updatedSerials });
                                                            }}
                                                            onKeyUp={(e) => {
                                                                if (e.key === 'Enter') {
                                                                    focusNextSerialInput(e, index);
                                                                }
                                                            }}
                                                        />
                                                    </div>
                                                    <div className="col-span-6 sm:col-span-3">
                                                        <Input
                                                            label={t('Till')}
                                                            value={serial.till}
                                                            onChange={(e) => {
                                                                const updatedSerials = [...data.serials];
                                                                updatedSerials[index].till = e.target.value;
                                                                setData({ ...data, serials: updatedSerials });
                                                            }}
                                                        />
                                                    </div>
                                                    {(serial.number || (serial.till && serial.number < serial.till)) && (
                                                        <div className="col-span-full -mt-3">
                                                            <strong>{countSerials(serial.number, serial.till)}</strong>{' '}
                                                            {t(countSerials(serial.number, serial.till) === 1 ? 'Serial Number' : 'Serial Numbers')}
                                                        </div>
                                                    )}
                                                </React.Fragment>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* SEO Fields */}
                        <div className="col-span-full mb-6 flex flex-col gap-6 rounded-sm border border-gray-200 p-6 dark:border-gray-700">
                            <div className="col-span-full -mb-3 -mt-6">
                                <h4 className="mb-3 mt-6 text-lg font-bold">{t('SEO Fields')}</h4>
                            </div>
                            <div>
                                <Input
                                    id="title"
                                    label={t('Title')}
                                    value={data.title}
                                    onChange={(e) => setData({ ...data, title: e.target.value })}
                                    error={errors.title}
                                />
                            </div>
                            <div>
                                <Textarea
                                    id="description"
                                    label={t('Description')}
                                    value={data.description}
                                    onChange={(e) => setData({ ...data, description: e.target.value })}
                                    error={errors.description}
                                />
                            </div>
                            <div>
                                <Textarea
                                    id="keywords"
                                    label={t('Keywords')}
                                    value={data.keywords}
                                    onChange={(e) => setData({ ...data, keywords: e.target.value })}
                                    error={errors.keywords}
                                />
                            </div>
                            <div className="flex gap-x-12 gap-y-4">
                                <Toggle
                                    id="noindex"
                                    label={t('Noindex')}
                                    checked={data.noindex}
                                    onChange={(checked) => setData({ ...data, noindex: checked })}
                                />
                                <Toggle
                                    id="nofollow"
                                    label={t('Nofollow')}
                                    checked={data.nofollow}
                                    onChange={(checked) => setData({ ...data, nofollow: checked })}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Form Actions */}
                    <div className="flex items-center justify-end pt-6">
                        {current && (
                            <SecondaryButton type="button" onClick={() => handleSubmit(undefined, true)} className="mr-3">
                                {t('Save & go to listing')}
                            </SecondaryButton>
                        )}

                        <ActionMessage on={recentlySuccessful} className="me-3 ms-3">
                            {t('Saved.')}
                        </ActionMessage>

                        <LoadingButton type="submit" loading={processing} className={processing ? 'opacity-25' : ''}>
                            {t('Save')}
                        </LoadingButton>
                    </div>
                </FormSection>
            </div>
        </AppLayout>
    );
}
