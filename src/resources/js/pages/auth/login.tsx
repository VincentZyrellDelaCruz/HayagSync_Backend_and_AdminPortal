import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle, LockKeyhole, Mail } from 'lucide-react';
import { SubmitEventHandler, useEffect, useState } from 'react';

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

export default function Login({ status, canResetPassword, }: LoginProps) {
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

    const [showPassword, setShowPassword] = useState(false);

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
            title="Welcome back"
            description="Sign in to access HayagSync and securely manage bullying incident records."
        >
            <Head title="Log in" />

            <div className="space-y-6">
                {/* Session Status */}
                {status && (
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-400">
                        {status}
                    </div>
                )}

                {/* Validation Errors */}
                {errors.email && errors.password && (
                    <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3.5 dark:border-red-900/60 dark:bg-red-950/30">
                        <p className="text-sm font-medium text-red-700 dark:text-red-400">
                            Unable to log in with the provided credentials.
                        </p>
                    </div>
                )}

                <form
                    className="space-y-5"
                    onSubmit={submit}
                >
                    {/* Email */}
                    <div className="space-y-2">
                        <Label
                            htmlFor="email"
                            className="text-sm font-semibold text-slate-700 dark:text-slate-200"
                        >
                            Email Address
                        </Label>

                        <div className="relative">
                            <Mail className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

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
                                placeholder="Enter your email address"
                                className="h-12 rounded-xl border-slate-200 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800/70 dark:text-white dark:focus:border-blue-500 dark:focus:bg-slate-800"
                            />
                        </div>

                        <InputError
                            message={errors.email}
                            className="mt-1"
                        />
                    </div>

                    {/* Password */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between gap-3">
                            <Label
                                htmlFor="password"
                                className="text-sm font-semibold text-slate-700 dark:text-slate-200"
                            >
                                Password
                            </Label>

                            {canResetPassword && (
                                <TextLink
                                    href={route(
                                        'password.request',
                                    )}
                                    className="text-xs font-medium text-blue-600 transition hover:text-blue-700 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                                    tabIndex={5}
                                >
                                    Forgot Password?
                                </TextLink>
                            )}
                        </div>

                        <div className="relative">
                            <LockKeyhole className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                            <Input
                                id="password"
                                type={
                                    showPassword
                                        ? 'text'
                                        : 'password'
                                }
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
                                className="h-12 rounded-xl border-slate-200 bg-slate-50 pl-10 pr-11 text-sm text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800/70 dark:text-white dark:focus:border-blue-500 dark:focus:bg-slate-800"
                            />

                            <button
                                type="button"
                                onClick={() =>
                                    setShowPassword(
                                        (value) => !value,
                                    )
                                }
                                className="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:hover:bg-slate-700 dark:hover:text-slate-200"
                                aria-label={
                                    showPassword
                                        ? 'Hide password'
                                        : 'Show password'
                                }
                                tabIndex={3}
                            >
                                {showPassword ? (
                                    <EyeOff className="h-4 w-4" />
                                ) : (
                                    <Eye className="h-4 w-4" />
                                )}
                            </button>
                        </div>

                        <InputError
                            message={errors.password}
                            className="mt-1"
                        />
                    </div>

                    {/* Remember + Forgot Password */}
                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="remember"
                            name="remember"
                            tabIndex={4}
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
                            className="cursor-pointer text-sm font-normal text-slate-600 dark:text-slate-300"
                        >
                            Remember my email
                        </Label>
                    </div>

                    {/* Login Button */}
                    <div className="pt-1">
                        <Button
                            type="submit"
                            tabIndex={5}
                            disabled={processing}
                            className="h-12 w-full justify-center rounded-xl bg-blue-600 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-500/20 cursor-pointer disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}

                            {processing
                                ? 'Signing in...'
                                : 'Log In'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthLayout>
    );
}
