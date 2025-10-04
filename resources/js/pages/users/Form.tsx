import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FormEventHandler, useState } from 'react';
import { productFormSchema } from '@/schemas/productFormSchema';
import { Product } from '@/types/product';
import { StepBack } from 'lucide-react';
import * as React from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Textarea } from '@/components/ui/textarea';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'New User',
        href: '/users/list'
    }
];
export default function Form({ current }) {

    const { t } = useTranslation()


    const form = useForm<Product>({
        id: current?.id || undefined,
        type: current?.type || "Standard",
        name: current?.name || "",
        secondary_name: current?.secondary_name || "",
        code: current?.code || "",
    })

    const { data, setData, post, processing, errors, reset, clearErrors } = form;

    // 4️⃣ Local state for client-side validation errors
    const [validationErrors, setValidationErrors] = useState<
        Partial<Record<keyof Product, string>>
    >({});

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
            <Head title="New User" />
            <div className="flex flex-col gap-4 py-4 md:gap-2 md:py-2">
                <div className="grid grid-cols-1 gap-6 md:grid-cols-[1fr_2fr]">
                    <div className="gap-4">
                        <Card className="me-2 ms-2 gap-0 border-none">
                            <CardHeader>
                                <CardTitle>{t('New User')}</CardTitle>
                            </CardHeader>
                        </Card>
                    </div>
                    <Card className="me-2 ms-2 gap-0">
                        <form className="" onSubmit={submit}>
                            <CardHeader>
                                <CardAction>
                                    <div className="flex items-center gap-2">
                                        <div className="flex items-center gap-2">
                                            <Link
                                                href={route('auth.users.list')}
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
                                <div className="grid grid-cols-2 gap-6 md:grid-cols-2">
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
                                        <InputError message={validationErrors.barcode_code || errors.barcode_code}
                                                    className="mt-2" />
                                    </div>
                                    <div className="col-span-2 grid gap-2">
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
                                </div>
                            </CardContent>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    )
}
