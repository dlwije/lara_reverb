import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Product } from '@/types/product';
import Form from '@/Core/form';
import { productFormSchema } from '@/schemas/productFormSchema';
import AuthHeader from '@/layouts/auth/AuthHeader';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products'
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

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
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
            <Head title="Register" />
            <form className="p-8" onSubmit={submit}>
                <div className="flex flex-col gap-6">
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
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            tabIndex={2}
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            disabled={processing}
                            placeholder="email@example.com"
                        />
                        <InputError message={validationErrors.email || errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            tabIndex={3}
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            disabled={processing}
                            placeholder="Password"
                        />
                        <InputError message={validationErrors.password || errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            tabIndex={4}
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            disabled={processing}
                            placeholder="Confirm password"
                        />
                        <InputError
                            message={
                                validationErrors.password_confirmation ||
                                errors.password_confirmation
                            }
                        />
                    </div>

                    <Button type="submit" className="mt-2 w-full" tabIndex={5} disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Create account
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}

