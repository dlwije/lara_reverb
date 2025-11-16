'use client';

import type React from 'react';
import type { FormEventHandler } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';
import { ArrowLeft, HelpCircle, LoaderCircle, Shield } from 'lucide-react';
import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { cardFormSchema } from '@/schemas/cardFormSchema';
import InputError from '@/components/input-error';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cards & Accounts',
        href: '/wallet/add-card',
    },
];

type CardForm = {
    cardholder_name: string,
    card_number: string,
    expiry_date: string,
    expiry_month: string,
    expiry_year: string,
    cvv: string,
}
export default function AddCardPage() {

    const form = useForm<Required<CardForm>>({
        cardholder_name: '',
        card_number: '',
        expiry_date: '',
        expiry_month: '',
        expiry_year: '',
        cvv: '',
    });

    const { data, setData, post, processing, errors, clearErrors } = form;

    const [touched, setTouched] = useState<{ [key: string]: boolean }>({});
    const [validationErrors, setValidationErrors] = useState<
        Partial<Record<keyof CardForm, string>>
    >({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        clearErrors();
        setValidationErrors({});

        const result = cardFormSchema.safeParse(data);
        if (!result.success) {
            const fieldErrors: Partial<Record<keyof CardForm, string>> = {};
            for (const [key, error] of Object.entries(result.error.format())) {
                if ('_errors' in error && error._errors.length) {
                    fieldErrors[key as keyof CardForm] = error._errors[0];
                }
            }
            setValidationErrors(fieldErrors);
            return;
        }

        // 🔒 send to backend (but never store card raw data — use Stripe or gateway!)
        post(route("cards.store"), {
            onError: (errors) => {
                setValidationErrors(errors as Partial<Record<keyof CardForm, string>>);
            },
            onSuccess: () => {
                setData({
                    card_number: "",
                    expiry_date: "",
                    cvv: "",
                    cardholder_name: "",
                });
                setValidationErrors({});
                setTouched({});
            },
        });
    };

    const handleBack = () => {
        window.history.back();
    };

    const formatCardNumber = (value: string) => {
        // Remove all non-digits
        const digits = value.replace(/\D/g, '');
        // Add spaces every 4 digits
        return digits.replace(/(\d{4})(?=\d)/g, '$1 ');
    };

    const formatExpiryDate = (value: string) => {
        // Remove all non-digits
        const digits = value.replace(/\D/g, '');
        // Add slash after 2 digits
        if (digits.length >= 2) {
            return digits.slice(0, 2) + '/' + digits.slice(2, 4);
        }
        return digits;
    };

    const validateField = (field: keyof CardForm, value: any) => {
        const partialData = { [field]: value };

        const fieldSchema = cardFormSchema.pick({ [field]: true });

        const result = fieldSchema.safeParse(partialData);

        if (!result.success) {
            return result.error.errors[0]?.message || "Invalid value";
        }

        return null;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            {/*<Head title="Cards & Accounts" />*/}
            <SettingsLayout>
                <div className="bg-background min-h-screen">
                    {/* Header */}
                    <div className="flex items-center gap-4 border-b p-4">
                        <Button variant="ghost" size="icon" onClick={handleBack} className="bg-muted/50 h-10 w-10 rounded-lg border">
                            <ArrowLeft className="h-5 w-5" />
                        </Button>
                        <h1 className="text-lg font-semibold">Add new card</h1>
                    </div>

                    <div className="space-y-6 p-4">
                        {/* Note */}
                        <div className="bg-muted/50 rounded-lg p-4">
                            <p className="text-muted-foreground text-sm">
                                <span className="font-medium">Note</span> In order to verify your account we may charge your account with small amount
                                that will be refunded.
                            </p>
                        </div>

                        {/* Form */}
                        <form onSubmit={submit} className="space-y-6">
                            {/* Card Number */}
                            <div className="space-y-2">
                                <Label htmlFor="cardNumber" className="text-base font-medium">
                                    Card number
                                </Label>
                                <Input
                                    id="cardNumber"
                                    type="text"
                                    placeholder="1234 5678 9012 3456"
                                    value={formatCardNumber(data.card_number)}
                                    onChange={(e) => setData('card_number', e.target.value)}
                                    className={`h-12 ${validationErrors.card_number || errors.card_number ? 'border-red-500' : ''}`}
                                    maxLength={19} // 16 digits + 3 spaces
                                    disabled={processing}
                                />
                                {/*{errors.cardNumber && <p className="text-sm text-red-500">{errors.cardNumber}</p>}*/}
                                <InputError message={validationErrors.card_number || errors.card_number} />
                            </div>

                            {/* Expiry Date and CVV */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="expiryDate" className="flex items-center gap-2 text-base font-medium">
                                        Expiry date
                                        <HelpCircle className="text-muted-foreground h-4 w-4" />
                                    </Label>
                                    <Input
                                        id="expiryDate"
                                        type="text"
                                        placeholder="MM/YY"
                                        value={formatExpiryDate(data.expiry_date)}
                                        onChange={(e) => setData('expiry_date', e.target.value)}
                                        className={`h-12 ${validationErrors.expiry_date || errors.expiry_date ? 'border-red-500' : ''}`}
                                        maxLength={5}
                                        disabled={processing}
                                    />
                                    {/*{errors.expiryDate && <p className="text-sm text-red-500">{errors.expiryDate}</p>}*/}
                                    <InputError message={validationErrors.expiry_date || errors.expiry_date} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="cvv" className="flex items-center gap-2 text-base font-medium">
                                        CVV
                                        <HelpCircle className="text-muted-foreground h-4 w-4" />
                                    </Label>
                                    <Input
                                        id="cvv"
                                        type="text"
                                        placeholder="123"
                                        value={data.cvv}
                                        onChange={(e) => setData('cvv', e.target.value.replace(/\D/g, ''))}
                                        className={`h-12 ${validationErrors.cvv || errors.cvv ? 'border-red-500' : ''}`}
                                        maxLength={4}
                                        disabled={processing}
                                    />
                                    {/*{errors.cvv && <p className="text-sm text-red-500">{errors.cvv}</p>}*/}
                                    <InputError message={validationErrors.cvv || errors.cvv} />
                                </div>
                            </div>

                            {/* Nickname */}
                            <div className="space-y-2">
                                <Label htmlFor="nickname" className="text-base font-medium">
                                    Nickname(optional)
                                </Label>
                                <Input
                                    id="nickname"
                                    type="text"
                                    placeholder="Peter Mcdonalds"
                                    value={data.cardholder_name}
                                    onChange={(e) => setData('cardholder_name', e.target.value)}
                                    className={`h-12 ${errors.cardholder_name ? 'border-red-500' : ''}`}
                                    disabled={processing}
                                />
                            </div>

                            {/* Payment Method Logos */}
                            <div className="flex items-center gap-3 py-4">
                                <div className="flex h-8 w-12 items-center justify-center rounded bg-purple-600 text-xs font-bold text-white">
                                    CLUB
                                </div>
                                <div className="flex h-8 w-12 items-center justify-center rounded bg-blue-600 text-xs font-bold text-white">VISA</div>
                                <div className="flex h-8 w-12 items-center justify-center rounded bg-gradient-to-r from-red-500 to-orange-500 text-xs font-bold text-white">
                                    MC
                                </div>
                                <div className="flex h-8 w-12 items-center justify-center rounded bg-blue-500 text-xs font-bold text-white">AMEX</div>
                                <div className="flex h-8 w-12 items-center justify-center rounded bg-green-500 text-xs font-bold text-white">UNI</div>
                            </div>

                            {/* Security Message */}
                            <div className="flex items-center justify-center gap-2 py-6">
                                <Shield className="text-muted-foreground h-5 w-5" />
                                <p className="text-muted-foreground text-sm">Your payment info is stored securely</p>
                            </div>

                            {/* Submit Button */}
                            <Button type="submit" className="h-12 w-full text-base font-medium" disabled={processing} tabIndex={5}>
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                Add Card
                            </Button>
                        </form>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
