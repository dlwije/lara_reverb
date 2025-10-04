import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect, useState } from 'react';
import { Product } from '@/types/product';
import Form from '@/Core/form';
import { productFormSchema } from '@/schemas/productFormSchema';
import AuthHeader from '@/layouts/auth/AuthHeader';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Card, CardAction, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge, LoaderCircle, Plus, Save, StepBack } from 'lucide-react';
import * as React from 'react';
import { useTranslation } from '@/hooks/use-translation';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { cn } from '@/lib/utils';
import AutoCompleteDrop from '@/components/AutoCompleteDrop';
import { Textarea } from '@/components/ui/textarea';

// https://github.com/birobirobiro/awesome-shadcn-ui
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products'
    }
];

const statuses = [
    {
        value: "standard",
        label: "Standard",
        color: "bg-gray-100 text-gray-800",
        icon: "⭕"
    },
    {
        value: "service",
        label: "Service",
        color: "bg-yellow-100 text-yellow-800",
        icon: "⏳"
    },
    {
        value: "digital",
        label: "Digital",
        color: "bg-green-100 text-green-800",
        icon: "✅"
    },
    {
        value: "combo",
        label: "Combo",
        color: "bg-red-100 text-red-800",
        icon: "❌"
    },
    {
        value: "recipe",
        label: "Recipe",
        color: "bg-blue-100 text-blue-800",
        icon: "🌐"
    },

]

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

    const { t } = useTranslation()
    const [selected, setSelected] = React.useState("draft")

    const selectedStatus = statuses.find(status => status.value === selected)

    const form = useForm<Product>({
        id: current?.id || undefined,
        type: current?.type || "Standard",
        name: current?.name || "",
        secondary_name: current?.secondary_name || "",
        code: current?.code || "",
        symbology: current?.symbology || "",
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
        rack_location: current?.rack_location || "",
        weight: current?.weight || undefined,
        dimensions: current?.dimensions || "",
        hsn_number: current?.hsn_number || "",
        sac_number: current?.sac_number || "",
        supplier_id: current?.supplier_id || undefined,
        supplier_part_id: current?.supplier_part_id || "",
        alert_quantity: current?.alert_quantity || undefined,
        video_url: current?.video_url || "",
        details: current?.details || "",
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
        title: current?.title || "",
        description: current?.description || "",
        keywords: current?.keywords || "",
        noindex: current?.noindex || false,
        nofollow: current?.nofollow || false,
        photo: current?.photo || "",
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
                taxes: [],
            })),
        serials: current?.serials || [],
        extra_attributes: current?.extra_attributes || {},
        file: current?.file || [],
        photos: current?.photos || [],
    })

    const { data, setData, post, processing, errors, reset, clearErrors } = form;

    // 4️⃣ Local state for client-side validation errors
    const [validationErrors, setValidationErrors] = useState<
        Partial<Record<keyof Product, string>>
    >({});

    // useEffect(() => {
    //     console.log(selected)
    // }, [selected]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        console.log("submit", e);
        const validationResult = productFormSchema.safeParse({
            name: form.data.name,
            code: form.data.code,
            price: form.data.price,
            cost: form.data.cost,
            weight: form.data.weight,
            alert_quantity: form.data.alert_quantity,
            video_url: form.data.video_url,
            details: form.data.details,
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
    }
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
                                <div className="rounded-md p-6">
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        {/* Left column (empty) */}
                                        <div className="grid gap-6">
                                            <div className="grid gap-2">
                                                <label className="mb-1 text-sm font-medium">{t('Type')}</label>
                                                <Select value={selected} onValueChange={setSelected}>
                                                    <SelectTrigger className="h-10">
                                                        <SelectValue>
                                                            {selectedStatus && (
                                                                <div className="flex items-center gap-2">
                                                                    <span>{selectedStatus.icon}</span>
                                                                    <span className="ms-2">{selectedStatus.label}</span>
                                                                    {/*<Badge variant="secondary" className={cn("text-xs", selectedStatus.color)}>*/}
                                                                    {/*    */}
                                                                    {/*</Badge>*/}
                                                                </div>
                                                            )}
                                                            {!selectedStatus && (
                                                                <SelectValue>
                                                                    <div className="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                                                        <span className="text-dark-800">Select</span>
                                                                    </div>
                                                                </SelectValue>
                                                            )}
                                                        </SelectValue>
                                                    </SelectTrigger>
                                                    <SelectContent className="">
                                                        {statuses.map((status) => (
                                                            <SelectItem key={status.value} value={status.value} className="text-foreground">
                                                                <div className="flex w-full items-center gap-2">
                                                                    <span>{status.icon}</span>
                                                                    <span className="ms-2">{status.label}</span>
                                                                </div>
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="name">Name</Label>
                                                <Input
                                                    id="name"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="name"
                                                    value={data.name}
                                                    onChange={(e) => setData('name', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.name || errors.name} className="mt-2" />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="barcode_code">
                                                    Code (barcode)<span className="text-gray-400">Generate</span>
                                                </Label>
                                                <Input
                                                    id="barcode_code"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="barcode_code"
                                                    value={data.barcode_code}
                                                    onChange={(e) => setData('barcode_code', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.barcode_code || errors.barcode_code} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <label className="mb-1 text-sm font-medium">{t('Category')}</label>
                                                <AutoCompleteDrop />
                                            </div>
                                            <div className="grid gap-2">
                                                <label className="mb-1 text-sm font-medium">{t('Brand')}</label>
                                                <AutoCompleteDrop />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="purchase_cost">Purchase Cost</Label>
                                                <Input
                                                    id="purchase_cost"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="purchase_cost"
                                                    value={data.purchase_cost}
                                                    onChange={(e) => setData('purchase_cost', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.purchase_cost || errors.purchase_cost} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="minimum_price">Minimum Price</Label>
                                                <Input
                                                    id="minimum_price"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="minimum_price"
                                                    value={data.minimum_price}
                                                    onChange={(e) => setData('minimum_price', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.minimum_price || errors.minimum_price} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="maximum_discount">Maximum Discount</Label>
                                                <Input
                                                    id="maximum_discount"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="maximum_discount"
                                                    value={data.maximum_discount}
                                                    onChange={(e) => setData('maximum_discount', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.maximum_discount || errors.maximum_discount} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="weight">Weight</Label>
                                                <Input
                                                    id="weight"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="weight"
                                                    value={data.weight}
                                                    onChange={(e) => setData('weight', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.weight || errors.weight} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="hsn_number">HSN number</Label>
                                                <Input
                                                    id="hsn_number"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="hsn_number"
                                                    value={data.hsn_number}
                                                    onChange={(e) => setData('hsn_number', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.hsn_number || errors.hsn_number} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="supplier_part_id">{t('Supplier part id')}</Label>
                                                <Input
                                                    id="supplier_part_id"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="supplier_part_id"
                                                    value={data.supplier_part_id}
                                                    onChange={(e) => setData('supplier_part_id', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.supplier_part_id || errors.supplier_part_id} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="photo">{t('Photo')}</Label>
                                                <Input
                                                    id="photo"
                                                    type="file"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="photo"
                                                    value={data.photo}
                                                    onChange={(e) => setData('photo', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.photo || errors.photo} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="photos">{t('Photos')}</Label>
                                                <Input
                                                    id="photos"
                                                    type="file"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="photos"
                                                    value={data.photos}
                                                    onChange={(e) => setData('photos', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.photos || errors.photos} className="mt-2" />
                                            </div>
                                            <div className="grid gap-4">
                                                <Label htmlFor="video_url">{t('Video URL')}</Label>
                                                <Input
                                                    id="video_url"
                                                    type="url"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="video_url"
                                                    value={data.video_url}
                                                    onChange={(e) => setData('video_url', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.video_url || errors.video_url} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="product_features">{t('Product Features')}</Label>
                                                <Textarea
                                                    id="product_features"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="product_features"
                                                    value={data.product_features}
                                                    onChange={(e) => setData('product_features', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.product_features || errors.product_features} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="product_details">{t('Product Details')}</Label>
                                                <Textarea
                                                    id="product_details"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="product_details"
                                                    value={data.product_details}
                                                    onChange={(e) => setData('product_details', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.product_details || errors.product_details} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2 border">
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Featured</span>
                                                </Label>
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Hide in POS</span>
                                                </Label>
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Hide in Shop</span>
                                                </Label>
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Tax is included in price</span>
                                                </Label>
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Allow to change price while selling</span>
                                                </Label>
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Has expiry date (will show expiry date while purchasing)</span>
                                                </Label>
                                            </div>
                                            <div className="grid gap-2 border">
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Has variants</span>
                                                </Label>
                                            </div>
                                            <div className="grid gap-2 border">
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Do not track stock</span>
                                                </Label>
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Set different price per store</span>
                                                </Label>
                                            </div>
                                            <div className="grid gap-2 border">
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Has serial numbers</span>
                                                </Label>
                                            </div>
                                            <div className="grid gap-4">
                                                <Label htmlFor="seo_title">{t('Title')}</Label>
                                                <Input
                                                    id="seo_title"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="seo_title"
                                                    value={data.seo_title}
                                                    onChange={(e) => setData('seo_title', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.seo_title || errors.seo_title} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="seo_description">{t('Description')}</Label>
                                                <Textarea
                                                    id="seo_description"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="seo_description"
                                                    value={data.seo_description}
                                                    onChange={(e) => setData('seo_description', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.seo_description || errors.seo_description} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="seo_keywords">{t('Keywords')}</Label>
                                                <Textarea
                                                    id="seo_keywords"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="seo_keywords"
                                                    value={data.seo_keywords}
                                                    onChange={(e) => setData('seo_keywords', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.seo_keywords || errors.seo_keywords} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2 border">
                                                <Label className="flex items-center gap-2 text-sm">
                                                    <Switch className="shadow-none" />
                                                    <span>Noindex</span>
                                                    <Switch className="shadow-none" />
                                                    <span>Nofollow</span>
                                                </Label>
                                            </div>
                                        </div>

                                        {/* Textarea spanning both columns */}
                                        <div className="grid gap-2 md:col-span-2">
                                            <Label htmlFor="description">Description</Label>
                                            <textarea
                                                id="description"
                                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                                value={data.description}
                                                onChange={(e) => setData('description', e.target.value)}
                                                placeholder="Enter detailed description"
                                                rows={4}
                                                disabled={processing}
                                            />
                                            <InputError message={validationErrors.description || errors.description} className="mt-2" />
                                        </div>

                                        {/* Right column (all form fields + button) */}
                                        <div className="grid gap-6">
                                            {/*<div className="grid gap-2 my-7"></div>*/}
                                            <div className="grid gap-2">
                                                <label className="mb-1 text-sm font-medium">{t('Tax')}</label>
                                                <AutoCompleteDrop />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="secondary_name">Secondary Name</Label>
                                                <Input
                                                    id="secondary_name"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="secondary_name"
                                                    value={data.secondary_name}
                                                    onChange={(e) => setData('secondary_name', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.secondary_name || errors.secondary_name} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <label className="mb-1 text-sm font-medium">{t('Symbology')}</label>
                                                <AutoCompleteDrop />
                                            </div>
                                            <div className="grid gap-2">
                                                <label className="mb-1 text-sm font-medium">{t('Subcategory')}</label>
                                                <AutoCompleteDrop />
                                            </div>
                                            <div className="grid gap-2">
                                                <label className="mb-1 text-sm font-medium">{t('Unit')}</label>
                                                <AutoCompleteDrop />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="selling_price">Selling Price</Label>
                                                <Input
                                                    id="selling_price"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="selling_price"
                                                    value={data.selling_price}
                                                    onChange={(e) => setData('selling_price', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.selling_price || errors.selling_price} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="maximum_price">Maximum Price</Label>
                                                <Input
                                                    id="maximum_price"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="maximum_price"
                                                    value={data.maximum_price}
                                                    onChange={(e) => setData('maximum_price', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.maximum_price || errors.maximum_price} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="rack_location">Rack Location</Label>
                                                <Input
                                                    id="rack_location"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="rack_location"
                                                    value={data.rack_location}
                                                    onChange={(e) => setData('rack_location', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.rack_location || errors.rack_location} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="dimentions">{t('Dimensions')}</Label>
                                                <Input
                                                    id="dimentions"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="dimentions"
                                                    value={data.dimentions}
                                                    onChange={(e) => setData('dimentions', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.dimentions || errors.dimentions} className="mt-2" />
                                            </div>
                                            <div className="grid gap-2">
                                                <label className="mb-1 text-sm font-medium">{t('Supplier')}</label>
                                                <AutoCompleteDrop />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="low_stock_qty">{t('Alert (low stock) QTY')}</Label>
                                                <Input
                                                    id="low_stock_qty"
                                                    type="text"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="low_stock_qty"
                                                    value={data.low_stock_qty}
                                                    onChange={(e) => setData('low_stock_qty', e.target.value)}
                                                    disabled={processing}
                                                    placeholder="Full name"
                                                />
                                                <InputError message={validationErrors.low_stock_qty || errors.low_stock_qty} className="mt-2" />
                                            </div>
                                            <div className=""></div>
                                            <div className=""></div>
                                            <div className=""></div>

                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter className="flex items-center justify-between py-4">
                                <div></div>
                                <Button
                                    type="submit"
                                    tabIndex={5}
                                    disabled={processing}
                                    variant="outline"
                                    className="inline-flex h-8 items-center gap-1 whitespace-nowrap bg-transparent"
                                    asChild
                                >
                                    <span>
                                        <Save className="h-4 w-4" />
                                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                        {t('Save')}
                                    </span>
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

