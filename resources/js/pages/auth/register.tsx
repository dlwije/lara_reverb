import { Head, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';


import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import AuthHeader from '@/layouts/auth/AuthHeader';
import { registerSchema } from '@/schemas/registerSchema';

type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function Register() {
    // const { data, setData, post, processing, errors, reset } = useForm<Required<RegisterForm>>({
    //     name: '',
    //     email: '',
    //     password: '',
    //     password_confirmation: '',
    // });

    // Inertia useForm
    const form = useForm<Required<RegisterForm>>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const { data, setData, post, processing, errors, reset, clearErrors } = form;

    // 4️⃣ Local state for client-side validation errors
    const [validationErrors, setValidationErrors] = useState<
        Partial<Record<keyof RegisterForm, string>>
    >({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const result = registerSchema.safeParse(data);

        if (!result.success) {
            const fieldErrors: Partial<Record<keyof RegisterForm, string>> = {};
            for (const [key, error] of Object.entries(result.error.format())) {
                if ('_errors' in error && error._errors.length) {
                    fieldErrors[key as keyof RegisterForm] = error._errors[0];
                }
            }
            setValidationErrors(fieldErrors);
            return;
        }

        //Clear previous server-side (Inertia) errors
        form.clearErrors();

        // Clear previous validation errors
        setValidationErrors({});

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    // ✅ Listen for token/user in flash props
    const { auth_token, auth_user } = usePage().props;

    useEffect(() => {
    // console.log('Auth Token: '+auth_token);
    // console.log('Auth User: '+ auth_user);
        if (auth_token) {
            if (typeof auth_token === 'string') {
                localStorage.setItem('access_token', auth_token);
            }
        }
        if (auth_user) {
            localStorage.setItem('auth_user', JSON.stringify(auth_user));
        }
    }, [auth_token, auth_user]);

    return (
        <AuthLayout title="Create an account" description="Enter your details below to create your account">
            <Head title="Register" />
            <form className="p-8" onSubmit={submit}>
                <div className="flex flex-col gap-6">
                    <AuthHeader title="Taggo.ae" description="Sign up to your Taggo Inc account" />
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

                <div className="text-center text-sm text-muted-foreground">
                    Already have an account?{' '}
                    <TextLink href={route('login')} tabIndex={6}>
                        Log in
                    </TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}
