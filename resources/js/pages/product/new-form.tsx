import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect, useState } from 'react';
import { Product } from '@/types/product';
import { productFormSchema } from '@/schemas/productFormSchema';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Card, CardAction, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge, LoaderCircle, Plus, Save, StepBack } from 'lucide-react';
import * as React from 'react';
import { useTranslation } from '@/hooks/use-translation';

import AutoCompleteDrop from '@/components/AutoCompleteDrop';
import { Textarea } from '@/components/ui/textarea';
import { route } from 'ziggy-js';
import { InertiaDropdown } from '@/components/inertiaDropdown';

// https://github.com/birobirobiro/awesome-shadcn-ui
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products'
    }
];

const statuses = [
    {
        id: 'standard',
        name: 'Standard',
        color: 'bg-gray-100 text-gray-800',
        icon: '⭕'
    },
    {
        id: 'service',
        name: 'Service',
        color: 'bg-yellow-100 text-yellow-800',
        icon: '⏳'
    },
    {
        id: 'digital',
        name: 'Digital',
        color: 'bg-green-100 text-green-800',
        icon: '✅'
    },
    {
        id: 'combo',
        name: 'Combo',
        color: 'bg-red-100 text-red-800',
        icon: '❌'
    },
    {
        id: 'recipe',
        name: 'Recipe',
        color: 'bg-blue-100 text-blue-800',
        icon: '🌐'
    }

];

interface FormProps {
    current?: Product;
    categories: any[];
    brands: any[];
    units: any[];
    taxes: any[];
    stores: any[];
    custom_fields: any[];
}

export default function NewForm({ current, categories = [], brands = [], units = [], taxes = [], stores = [], custom_fields = [] }: FormProps) {

    const { t } = useTranslation();
    const [selected, setSelected] = useState('draft');
    const [categorySelection, setCategorySelection] = useState<string>('');

    const form = useForm<Product>({
        id: current?.id || undefined,
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
        photo: current?.photo || '',
        products: current?.products || [],
        taxes: current?.taxes || [],
        unit_prices: current?.unit_prices || {},
        variants: current?.variants || [],
        variations: current?.variations || [],
        stores:
            current?.stores ||
            (stores || []).map((store) => ({
                id: store.id,
                price: null,
                quantity: null,
                alert_quantity: null,
                taxes: []
            })),
        serials: current?.serials || [],
        extra_attributes: current?.extra_attributes || {},
        file: current?.file || [],
        photos: current?.photos || []
    });

    const { data, setData, post, processing, errors, reset, clearErrors } = form;

    // 4️⃣ Local state for client-side validation errors
    const [validationErrors, setValidationErrors] = useState<
        Partial<Record<keyof Product, string>>
    >({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        console.log('submit', e);
        const validationResult = productFormSchema.safeParse({
            name: form.data.name,
            code: form.data.code,
            price: form.data.price,
            cost: form.data.cost,
            weight: form.data.weight,
            alert_quantity: form.data.alert_quantity,
            video_url: form.data.video_url,
            details: form.data.details
        });

        if (!validationResult.success) {
            const fieldErrors: Partial<Record<keyof Product, string>> = {};
            for (const [key, error] of Object.entries(validationResult.error.format())) {
                if ('_errors' in error && error._errors.length) {
                    fieldErrors[key as keyof Product] = error._errors[0];
                }
            }
            setValidationErrors(fieldErrors);
            return;
        }

        //Clear previous server-side (Inertia) errors
        form.clearErrors();

        // Clear previous validation errors
        setValidationErrors({});

        post(route('admin.products.store'), {
            forceFormData: true,
            onSuccess: () => {
                // maybe reset form or show success
            },
            onFinish: () => reset('name','code')
        });
    };

    const handleCateChange = (e) => {
        setCategorySelection(e);
    };
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('New Product')} />
            <div className="flex flex-col gap-4 py-4 md:gap-2 md:py-2">
                <div className="grid grid-cols-1 gap-6 md:grid-cols-[1fr_2fr]">
                    <div className="grid gap-4">
                        <Card className="me-2 ms-2 gap-0 border-none">
                            <CardHeader>
                                <CardTitle>{t('New Product')}</CardTitle>
                            </CardHeader>
                        </Card>
                    </div>
                    <Card className="me-2 ms-2 gap-0">
                        <form className="" onSubmit={submit}>
                            <CardHeader>
                                <CardAction>
                                    <div className="flex items-center gap-2">
                                        {/*<Button size="sm" className="flex h-8 gap-1" asChild>*/}

                                        <div className="flex items-center gap-2">
                                            <Link
                                                href={route('admin.products.index')}
                                                className="inline-flex h-8 items-center justify-center gap-1 whitespace-nowrap rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <StepBack className="h-4 w-4" />
                                                {'Back'}
                                            </Link>
                                        </div>
                                    </div>
                                </CardAction>
                            </CardHeader>
                            <CardContent>
                                <div className="gap-3 py-3"></div>
                                <div className="grid grid-cols-2 gap-6 md:grid-cols-2">
                                    {/* Left column (empty) */}
                                    <div className="grid gap-2">
                                        <label className="text-sm font-medium">{t('Type')}</label>
                                        <InertiaDropdown options={statuses} selected={data.type}
                                                         setSelected={(value) => setData('type', value)} />
                                    </div>
                                    <div className="grid mt-2 gap-2">
                                        <Label htmlFor="code">
                                            Code (barcode)<span className="text-gray-400">Generate</span>
                                        </Label>
                                        <Input
                                            id="code"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="code"
                                            value={data.code}
                                            onChange={(e) => setData('code', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.code || errors.code}
                                                    className="mt-2" />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            type="text"

                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.name || errors.name}
                                                    className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="secondary_name">Secondary Name</Label>
                                        <Input
                                            id="secondary_name"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="secondary_name"
                                            value={data.secondary_name}
                                            onChange={(e) => setData('secondary_name', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError
                                            message={validationErrors.secondary_name || errors.secondary_name}
                                            className="mt-2" />
                                    </div>

                                    <div className="grid gap-2">
                                        <label className="text-sm font-medium">{t('Category')}</label>
                                        <AutoCompleteDrop endpoint={route('search.category')}
                                                          onSelect={(value) => setData('category_id', value)} />
                                    </div>
                                    <div className="grid gap-2">
                                        <label className="text-sm font-medium">{t('Subcategory')}</label>
                                        <AutoCompleteDrop endpoint={route('search.subCategory')} onSelect={(value) => setData('subcategory_id', value)}
                                                          extraParams={{ id: categorySelection.id }} />
                                    </div>
                                    <div className="grid gap-2">
                                        <label className="text-sm font-medium">{t('Brand')}</label>
                                        <InertiaDropdown options={brands} selected={data.brand_id}
                                                         setSelected={(value) => setData('brand_id', value)} />
                                    </div>
                                    <div className="grid gap-2">
                                        <label className="text-sm font-medium">{t('Unit')}</label>
                                        <InertiaDropdown options={units} selected={data.unit_id}
                                                         setSelected={(value) => setData('unit_id', value)} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="price">Selling Price</Label>
                                        <Input
                                            id="price"
                                            type="text"

                                            tabIndex={1}
                                            autoComplete="price"
                                            value={data.price}
                                            onChange={(e) => setData('price', parseFloat(e.target.value) || 0)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.price || errors.price}
                                                    className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="cost">Purchase Cost</Label>
                                        <Input
                                            id="cost"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="cost"
                                            value={data.cost}
                                            onChange={(e) => setData('cost', parseFloat(e.target.value) || 0)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.cost || errors.cost}
                                                    className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="min_price">Minimum Price</Label>
                                        <Input
                                            id="min_price"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="min_price"
                                            value={data.min_price}
                                            onChange={(e) => setData('min_price', parseFloat(e.target.value) || 0)}
                                            disabled={processing}
                                            placeholder=""
                                        />
                                        <InputError message={validationErrors.min_price || errors.min_price}
                                                    className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="max_price">Maximum Price</Label>
                                        <Input
                                            id="max_price"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="max_price"
                                            value={data.max_price}
                                            onChange={(e) => setData('max_price', parseFloat(e.target.value) || 0)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.max_price || errors.max_price}
                                                    className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="max_discount">Maximum Discount</Label>
                                        <Input
                                            id="max_discount"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="max_discount"
                                            value={data.max_discount}
                                            onChange={(e) => setData('max_discount', parseFloat(e.target.value) || 0)}
                                            disabled={processing}

                                        />
                                        <InputError
                                            message={validationErrors.max_discount || errors.max_discount}
                                            className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <label className="text-sm font-medium">{t('Tax')}</label>
                                        <InertiaDropdown options={taxes} selected={data.taxes}
                                                         setSelected={(value) => setData('taxes', value)} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="dimensions">{t('Dimensions')}</Label>
                                        <Input
                                            id="dimensions"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="dimensions"
                                            value={data.dimensions}
                                            onChange={(e) => setData('dimensions', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.dimensions || errors.dimensions}
                                                    className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="weight">Weight</Label>
                                        <Input
                                            id="weight"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="weight"
                                            value={data.weight}
                                            onChange={(e) => setData('weight', parseFloat(e.target.value) || 0)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.weight || errors.weight}
                                                    className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <label className="text-sm font-medium">{t('Supplier')}</label>
                                        <AutoCompleteDrop />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="hsn_number">HSN number</Label>
                                        <Input
                                            id="hsn_number"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="hsn_number"
                                            value={data.hsn_number}
                                            onChange={(e) => setData('hsn_number', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.hsn_number || errors.hsn_number}
                                                    className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="supplier_part_id">{t('Supplier part id')}</Label>
                                        <Input
                                            id="supplier_part_id"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="supplier_part_id"
                                            value={data.supplier_part_id}
                                            onChange={(e) => setData('supplier_part_id', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError
                                            message={validationErrors.supplier_part_id || errors.supplier_part_id}
                                            className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="alert_quantity">{t('Alert (low stock) QTY')}</Label>
                                        <Input
                                            id="alert_quantity"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="alert_quantity"
                                            value={data.alert_quantity}
                                            onChange={(e) => setData('alert_quantity', parseFloat(e.target.value) || 0)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.alert_quantity || errors.alert_quantity}
                                                    className="mt-2" />
                                    </div>
                                    <div className="grid gap-2">
                                        <label className="text-sm font-medium">{t('Symbology')}</label>
                                        <AutoCompleteDrop />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="rack_location">Rack Location</Label>
                                        <Input
                                            id="rack_location"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="rack_location"
                                            value={data.rack_location}
                                            onChange={(e) => setData('rack_location', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.rack_location || errors.rack_location}
                                                    className="mt-2" />
                                    </div>
                                    <div className="col-span-2 grid gap-2">
                                        <Label htmlFor="photo">{t('Photo')}</Label>
                                        <Input
                                            id="photo"
                                            type="file"
                                            accept="image/*"
                                            tabIndex={1}
                                            autoComplete="photo"
                                            onChange={(e) => setData('photo', e.target.files?.[0] || null)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.photo || errors.photo}
                                                    className="mt-2" />
                                    </div>
                                    <div className="col-span-2 grid gap-2">
                                        <Label htmlFor="photos">{t('Photos')}</Label>
                                        <Input
                                            id="photos"
                                            type="file"
                                            tabIndex={1}
                                            accept="image/*"
                                            multiple
                                            onChange={(e) => setData('photos', Array.from(e.target.files || []))}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.photos || errors.photos}
                                                    className="mt-2" />
                                    </div>
                                    <div className="col-span-2 grid gap-4">
                                        <Label htmlFor="video_url">{t('Video URL')}</Label>
                                        <Input
                                            id="video_url"
                                            type="url"


                                            tabIndex={1}
                                            autoComplete="video_url"
                                            value={data.video_url}
                                            onChange={(e) => setData('video_url', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.video_url || errors.video_url}
                                                    className="mt-2" />
                                    </div>
                                    <div className="col-span-2 grid gap-2">
                                        <Label htmlFor="features">{t('Product Features')}</Label>
                                        <Textarea
                                            id="features"


                                            tabIndex={1}
                                            autoComplete="features"
                                            value={data.features}
                                            onChange={(e) => setData('features', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError
                                            message={validationErrors.features || errors.features}
                                            className="mt-2" />
                                    </div>
                                    <div className="col-span-2 grid gap-2">
                                        <Label htmlFor="details">{t('Product Details')}</Label>
                                        <Textarea
                                            id="details"


                                            tabIndex={1}
                                            autoComplete="details"
                                            value={data.details}
                                            onChange={(e) => setData('details', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError
                                            message={validationErrors.details || errors.details}
                                            className="mt-2" />
                                    </div>
                                    <div className="col-span-2 grid gap-2 border rounded px-2 py-3">
                                        <Label className="flex items-center gap-2 text-sm">
                                            <Switch className="shadow-none" checked={data.featured} onCheckedChange={(val) => setData('featured', val)} />
                                            <span>Featured</span>
                                        </Label>
                                        <Label className="flex items-center gap-2 text-sm">
                                            <Switch className="shadow-none" checked={data.hide_in_pos} onCheckedChange={(val) => setData('hide_in_pos', val)} />
                                            <span>Hide in POS</span>
                                        </Label>
                                        <Label className="flex items-center gap-2 text-sm">
                                            <Switch className="shadow-none" checked={data.hide_in_shop} onCheckedChange={(val) => setData('hide_in_shop', val)} />
                                            <span>Hide in Shop</span>
                                        </Label>
                                        <Label className="flex items-center gap-2 text-sm">
                                            <Switch className="shadow-none" checked={data.tax_included} onCheckedChange={(val) => setData('tax_included', val)} />
                                            <span>Tax is included in price</span>
                                        </Label>
                                        <Label className="flex items-center gap-2 text-sm">
                                            <Switch className="shadow-none" checked={data.can_edit_price} onCheckedChange={(val) => setData('can_edit_price', val)} />
                                            <span>Allow to change price while selling</span>
                                        </Label>
                                        <Label className="flex items-center gap-2 text-sm">
                                            <Switch className="shadow-none" checked={data.has_expiry_date} onCheckedChange={(val) => setData('has_expiry_date', val)} />
                                            <span>Has expiry date (will show expiry date while purchasing)</span>
                                        </Label>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label className="flex items-center gap-2 text-sm">
                                            <Switch className="shadow-none" checked={data.has_variants} onCheckedChange={(val) => setData('has_variants', val)} />
                                            <span>Has variants</span>
                                        </Label>
                                        {/*<Label className="flex items-center gap-2 text-sm">*/}
                                        {/*    <Switch className="shadow-none" />*/}
                                        {/*    <span>Has serial numbers</span>*/}
                                        {/*</Label>*/}
                                    </div>
                                    <div className="col-span-2 grid gap-2 border rounded px-2 py-3">
                                        <Label className="flex items-center gap-2 text-sm">
                                            <Switch className="shadow-none" checked={data.dont_track_stock} onCheckedChange={(val) => setData('dont_track_stock', val)} />
                                            <span>Do not track stock</span>
                                        </Label>
                                        <Label className="flex items-center gap-2 text-sm">
                                            <Switch className="shadow-none" checked={data.set_stock} onCheckedChange={(val) => setData('set_stock', val)} />
                                            <span>Set different price per store</span>
                                        </Label>
                                    </div>
                                    <div className="grid gap-2">

                                    </div>
                                    <div className="col-span-2 grid gap-4">
                                        <Label htmlFor="title">{t('Title')}</Label>
                                        <Input
                                            id="title"
                                            type="text"


                                            tabIndex={1}
                                            autoComplete="title"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.title || errors.title}
                                                    className="mt-2" />
                                    </div>
                                    <div className="col-span-2 grid gap-2">
                                        <Label htmlFor="description">{t('Description')}</Label>
                                        <Textarea
                                            id="description"


                                            tabIndex={1}
                                            autoComplete="description"
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError
                                            message={validationErrors.description || errors.description}
                                            className="mt-2" />
                                    </div>
                                    <div className="col-span-2 grid gap-2">
                                        <Label htmlFor="keywords">{t('Keywords')}</Label>
                                        <Textarea
                                            id="keywords"


                                            tabIndex={1}
                                            autoComplete="keywords"
                                            value={data.keywords}
                                            onChange={(e) => setData('keywords', e.target.value)}
                                            disabled={processing}

                                        />
                                        <InputError message={validationErrors.keywords || errors.keywords}
                                                    className="mt-2" />
                                    </div>
                                    <div className="grid gap-2 border rounded px-2 py-3">
                                        <Label className="flex items-center gap-2 text-sm">
                                            <Switch className="shadow-none" checked={data.noindex} onCheckedChange={(val) => setData('noindex', val)} />
                                            <span>Noindex</span>
                                            <Switch className="shadow-none" checked={data.nofollow} onCheckedChange={(val) => setData('nofollow', val)} />
                                            <span>Nofollow</span>
                                        </Label>
                                    </div>

                                </div>
                            </CardContent>
                            <CardFooter className="flex items-center justify-between py-4">
                                <div></div>
                                <button
                                    type="submit"
                                    tabIndex={5}
                                    disabled={processing}
                                    className="inline-flex h-8 items-center justify-center gap-1 whitespace-nowrap rounded-md border border-transparent bg-blue-600 px-6 py-6 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span>
                                        <Save className="h-4 w-4" />
                                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                        {t('Save')}
                                    </span>
                                </button>
                            </CardFooter>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

