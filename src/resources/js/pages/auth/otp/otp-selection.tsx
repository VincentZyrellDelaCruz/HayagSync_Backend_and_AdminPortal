import AppLogoIcon from '@/components/app-logo-icon';
import { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Mail, MessageSquareText, ShieldCheck } from 'lucide-react';
import { FormEvent } from 'react';

export default function OtpSelection() {
    const { name, email, phone, hasPhone } = usePage<PageProps>().props;

    const { data, setData, post, processing, errors } = useForm({
        method: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!data.method) {
            return;
        }

        post(route('otp.send'));
    };

    return (
        <>
            <Head title="Security Verification" />

            <div className="flex min-h-dvh items-center justify-center bg-slate-50 px-4 py-8 dark:bg-slate-950">
                <div className="w-full max-w-md">
                    <div className="mb-8 flex justify-center">
                        <Link
                            href={route('home')}
                            className="inline-flex items-center gap-3"
                        >
                            <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                                <AppLogoIcon className="size-6 fill-current" />
                            </span>

                            <span className="text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                                {name}
                            </span>
                        </Link>
                    </div>

                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50 sm:p-8 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                        <div className="mb-8 text-center">
                            <div className="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                                <ShieldCheck className="h-7 w-7" />
                            </div>

                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                Verify your sign-in
                            </h1>

                            <p className="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500 dark:text-slate-400">
                                We noticed this is a new browser or device. Choose where you want to receive your verification code.
                            </p>
                        </div>

                        <form onSubmit={submit} className="space-y-3">
                            <button
                                type="button"
                                onClick={() => setData('method', 'email')}
                                className={`flex w-full items-center gap-4 rounded-2xl border p-4 text-left transition ${
                                    data.method === 'email'
                                        ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/10 dark:border-blue-400 dark:bg-blue-500/10'
                                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:hover:border-slate-600 dark:hover:bg-slate-800'
                                }`}
                            >
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    <Mail className="h-5 w-5" />
                                </span>

                                <span className="min-w-0 flex-1">
                                    <span className="block text-sm font-semibold text-slate-900 dark:text-white">
                                        Email
                                    </span>
                                    <span className="mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400">
                                        {email}
                                    </span>
                                </span>

                                <span
                                    className={`h-4 w-4 rounded-full border ${
                                        data.method === 'email'
                                            ? 'border-blue-600 bg-blue-600 ring-4 ring-blue-100 dark:border-blue-400 dark:bg-blue-400 dark:ring-blue-500/20'
                                            : 'border-slate-300 dark:border-slate-600'
                                    }`}
                                />
                            </button>

                            {hasPhone && (
                                <button
                                    type="button"
                                    onClick={() => setData('method', 'phone')}
                                    className={`flex w-full items-center gap-4 rounded-2xl border p-4 text-left transition ${
                                        data.method === 'phone'
                                            ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/10 dark:border-blue-400 dark:bg-blue-500/10'
                                            : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:hover:border-slate-600 dark:hover:bg-slate-800'
                                    }`}
                                    disabled={true} // Temporarily disabled as twilio is currently unavailable
                                >
                                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        <MessageSquareText className="h-5 w-5" />
                                    </span>

                                    <span className="min-w-0 flex-1">
                                        <span className="block text-sm font-semibold text-slate-900 dark:text-white">
                                            Phone
                                        </span>
                                        <span className="mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400">
                                            {phone}
                                        </span>
                                    </span>

                                    <span
                                        className={`h-4 w-4 rounded-full border ${
                                            data.method === 'phone'
                                                ? 'border-blue-600 bg-blue-600 ring-4 ring-blue-100 dark:border-blue-400 dark:bg-blue-400 dark:ring-blue-500/20'
                                                : 'border-slate-300 dark:border-slate-600'
                                        }`}
                                    />
                                </button>
                            )}

                            {errors.method && (
                                <p className="pt-1 text-sm text-red-600 dark:text-red-400">
                                    {errors.method}
                                </p>
                            )}

                            <button
                                type="submit"
                                disabled={processing || !data.method}
                                className="mt-5 w-full rounded-2xl bg-blue-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processing ? 'Sending code...' : 'Continue'}
                            </button>
                        </form>

                        <p className="mt-6 text-center text-xs leading-5 text-slate-400 dark:text-slate-500">
                            For your security, verification is required when signing in from a new browser or device.
                        </p>
                    </div>

                    <p className="mt-6 text-center text-xs text-slate-400 dark:text-slate-500">
                        © {new Date().getFullYear()} {name}. All rights reserved.
                    </p>
                </div>
            </div>
        </>
    );
}
