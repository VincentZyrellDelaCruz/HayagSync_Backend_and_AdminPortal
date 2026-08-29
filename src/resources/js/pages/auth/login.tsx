import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { SubmitEventHandler, useEffect } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

const REMEMBER_EMAIL_KEY = 'hayagsync_remembered_email';

export default function Login({
    status,
    canResetPassword,
}: LoginProps) {
    const getRememberedEmail = (): string => {
        if (typeof window === 'undefined') {
            return '';
        }

        return localStorage.getItem(REMEMBER_EMAIL_KEY) ?? '';
    };

    const rememberedEmail = getRememberedEmail();

    const { data, setData, post, processing, errors, reset } =
        useForm<LoginForm>({
            email: rememberedEmail,
            password: '',
            remember: rememberedEmail !== '',
        });

    /**
     * Keep the remembered email synchronized with localStorage.
     */
    useEffect(() => {
        if (!data.remember) {
            localStorage.removeItem(REMEMBER_EMAIL_KEY);
            return;
        }

        if (data.email.trim() !== '') {
            localStorage.setItem(
                REMEMBER_EMAIL_KEY,
                data.email.trim(),
            );
        }
    }, [data.email, data.remember]);

    const submit: SubmitEventHandler = (e) => {
        e.preventDefault();

        // Save or remove the remembered email before authentication. Password is NEVER stored.
        if (data.remember && data.email.trim() !== '') {
            localStorage.setItem(
                REMEMBER_EMAIL_KEY,
                data.email.trim(),
            );
        } else {
            localStorage.removeItem(REMEMBER_EMAIL_KEY);
        }

        post(route('login'), {
            onFinish: () => {
                reset('password');
            },
        });
    };

    return (
        <AuthLayout
            title="HayagSync"
            description="Smart Mobile Incident Support Application"
        >
            <Head title="Log in" />

            <div className="w-full max-w-md">
                <div className="rounded-2xl border border-gray-100 bg-white p-8 shadow-2xl dark:border-slate-500 dark:bg-slate-900">
                    {/* Session Status */}
                    {status && (
                        <div className="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-600">
                            {status}
                        </div>
                    )}

                    {/* Validation Errors */}
                    {errors.email && errors.password && (
                        <div className="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                            <p className="text-sm font-medium text-red-600">
                                Unable to log in with the provided
                                credentials.
                            </p>
                        </div>
                    )}

                    <form
                        className="space-y-6"
                        onSubmit={submit}
                    >
                        {/* Email */}
                        <div>
                            <Label
                                htmlFor="email"
                                className="mb-2 block font-medium text-slate-700 dark:text-white"
                            >
                                Email Address
                            </Label>

                            <Input
                                id="email"
                                type="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="username"
                                value={data.email}
                                onChange={(e) =>
                                    setData(
                                        'email',
                                        e.target.value,
                                    )
                                }
                                placeholder="Enter your email"
                                className="block w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            />

                            <InputError
                                message={errors.email}
                                className="mt-2"
                            />
                        </div>

                        {/* Password */}
                        <div>
                            <Label
                                htmlFor="password"
                                className="mb-2 block font-medium text-slate-700 dark:text-white"
                            >
                                Password
                            </Label>

                            <Input
                                id="password"
                                type="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) =>
                                    setData(
                                        'password',
                                        e.target.value,
                                    )
                                }
                                placeholder="Enter your password"
                                className="block w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            />

                            <InputError
                                message={errors.password}
                                className="mt-2"
                            />
                        </div>

                        {/* Remember + Forgot Password */}
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                    checked={data.remember}
                                    onCheckedChange={(checked) =>
                                        setData(
                                            'remember',
                                            checked === true,
                                        )
                                    }
                                />

                                <Label
                                    htmlFor="remember"
                                    className="cursor-pointer text-sm font-normal text-slate-600 dark:text-white"
                                >
                                    Remember me
                                </Label>
                            </div>

                            {canResetPassword && (
                                <TextLink
                                    href={route(
                                        'password.request',
                                    )}
                                    className="text-sm text-blue-600 hover:text-blue-700 hover:underline"
                                    tabIndex={5}
                                >
                                    Forgot Password?
                                </TextLink>
                            )}
                        </div>

                        {/* Login Button */}
                        <div>
                            <Button
                                type="submit"
                                tabIndex={4}
                                disabled={processing}
                                className="w-full justify-center rounded-xl bg-blue-600 py-3 text-base font-semibold text-white shadow-lg transition duration-200 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                {processing && (
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                )}

                                {processing
                                    ? 'Logging in...'
                                    : 'Log In'}
                            </Button>
                        </div>
                    </form>
                </div>

                {/* Footer */}
                <div className="mt-6 text-center text-xs text-gray-500">
                    © {new Date().getFullYear()} HayagSync. All rights
                    reserved.
                </div>
            </div>
        </AuthLayout>
    );
}
