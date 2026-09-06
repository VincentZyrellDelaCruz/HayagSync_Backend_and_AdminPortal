import AppLogoIcon from '@/components/app-logo-icon';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Home, ShieldAlert } from 'lucide-react';
import { PageProps } from '@/types';

type FallbackStatus = 401 | 403 | 404 | 419 | 429 | 500 | 503;

interface Props extends PageProps {
    status: FallbackStatus;
}

export default function Fallback() {
    const { status } = usePage<Props>().props;

    const titles: Record<FallbackStatus, string> = {
        401: 'Unauthorized',
        403: 'Access Forbidden',
        404: 'Page Not Found',
        419: 'Session Expired',
        429: 'Too Many Requests',
        500: 'Internal Server Error',
        503: 'Service Unavailable',
    };

    const descriptions: Record<FallbackStatus, string> = {
        401: 'You need to sign in before you can access this page.',
        403: 'You do not have permission to access this page.',
        404: 'The page you are looking for could not be found.',
        419: 'Your session has expired. Please refresh the page and try again.',
        429: 'Too many requests were sent. Please wait a moment and try again.',
        500: 'Something went wrong while processing your request.',
        503: 'HayagSync is temporarily unavailable. Please try again later.',
    };

    const title = titles[status] ?? 'Something went wrong';
    const description =
        descriptions[status] ??
        'An unexpected error occurred while processing your request.';

    return (
        <>
            <Head title={`${status} - ${title}`} />

            <div className="relative flex min-h-dvh items-center justify-center overflow-hidden bg-slate-50 px-4 py-8 dark:bg-slate-950">
                <div className="absolute left-1/2 top-0 h-96 w-96 -translate-x-1/2 rounded-full bg-blue-500/10 blur-3xl" />

                <div className="relative w-full max-w-lg">
                    <div className="rounded-3xl border border-slate-200/80 bg-white p-8 text-center shadow-xl shadow-slate-200/40 sm:p-10 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                        <p className="mt-6 text-sm font-bold tracking-widest text-blue-600 dark:text-blue-400">
                            ERROR {status}
                        </p>

                        <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-white">
                            {title}
                        </h1>

                        <p className="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">
                            {description}
                        </p>

                        <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                            <Link
                                href={route('home')}
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Go to Dashboard
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
