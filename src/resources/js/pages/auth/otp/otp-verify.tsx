import AppLogoIcon from '@/components/app-logo-icon';
import { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, ShieldCheck } from 'lucide-react';
import { FormEvent, useEffect, useRef } from 'react';

export default function OtpVerify() {
    const { name, method, destination } = usePage<PageProps>().props;
    const inputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors } = useForm({
        otp_code: '',
    });

    useEffect(() => {
        inputRef.current?.focus();
    }, []);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (data.otp_code.length !== 6) {
            return;
        }

        post(route('otp.verify.submit'));
    };

    const handleChange = (value: string) => {
        setData('otp_code', value.replace(/\D/g, '').slice(0, 6));
    };

    return (
        <>
            <Head title="Enter Verification Code" />

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
                                Enter verification code
                            </h1>

                            <p className="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500 dark:text-slate-400">
                                Enter the 6-digit code sent to your {method === 'email' ? 'email address' : 'phone number'}.
                            </p>

                            {destination && (
                                <p className="mt-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {destination}
                                </p>
                            )}
                        </div>

                        <form onSubmit={submit}>
                            <label
                                htmlFor="otp_code"
                                className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Verification code
                            </label>

                            <input
                                ref={inputRef}
                                id="otp_code"
                                name="otp_code"
                                type="text"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                maxLength={6}
                                value={data.otp_code}
                                onChange={(event) => handleChange(event.target.value)}
                                className={`w-full rounded-2xl border bg-slate-50 px-4 py-4 text-center text-2xl font-bold tracking-[0.45em] text-slate-900 outline-none transition placeholder:text-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-600 ${
                                    errors.otp_code
                                        ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10'
                                        : 'border-slate-200 dark:border-slate-700'
                                }`}
                                placeholder="••••••"
                                aria-invalid={!!errors.otp_code}
                            />

                            {errors.otp_code && (
                                <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {errors.otp_code}
                                </p>
                            )}

                            <button
                                type="submit"
                                disabled={processing || data.otp_code.length !== 6}
                                className="mt-5 flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processing ? (
                                    'Verifying...'
                                ) : (
                                    <>
                                        <CheckCircle2 className="h-4 w-4" />
                                        Verify and continue
                                    </>
                                )}
                            </button>
                        </form>

                        <Link
                            href={route('otp.select')}
                            className="mt-5 flex items-center justify-center gap-2 text-sm font-medium text-slate-500 transition hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Change verification method
                        </Link>
                    </div>

                    <p className="mt-6 text-center text-xs leading-5 text-slate-400 dark:text-slate-500">
                        The verification code expires after 5 minutes.
                    </p>
                </div>
            </div>
        </>
    );
}
