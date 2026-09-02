import AppLogoIcon from '@/components/app-logo-icon';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import classroomBg from '@/assets/classroom.jpg';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    const { name, quote } = usePage<SharedData>().props;

    return (
        <div className="min-h-dvh bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-white lg:grid lg:grid-cols-2">
            <div className="relative hidden min-h-dvh overflow-hidden lg:flex">
                <img src={classroomBg} alt="" className="absolute inset-0 h-full w-full object-cover" />

                <div className="absolute inset-0 bg-black/45" />
                <div className="absolute inset-0 bg-linear-to-br from-black/60 via-black/25 to-blue-950/50" />

                <div className="relative z-10 flex w-full flex-col p-10 xl:p-12">
                    <Link
                        href={route('home')}
                        className="inline-flex w-fit items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-3 py-2 text-lg font-semibold tracking-tight text-white shadow-lg backdrop-blur-md transition hover:bg-white/15"
                    >
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20 backdrop-blur-sm">
                            <AppLogoIcon className="size-6 fill-current text-white" />
                        </span>

                        <span>{name}</span>
                    </Link>

                    <div className="mt-auto max-w-2xl pb-2">
                        <div className="rounded-3xl border border-white/15 bg-black/25 p-7 shadow-2xl backdrop-blur-md xl:p-8">
                            <h2 className="max-w-2xl text-4xl font-bold leading-tight tracking-tight text-white xl:text-5xl">
                                Your child student’s safety, documented and defended.
                            </h2>

                            {quote && (
                                <blockquote className="mt-8 border-l-2 border-blue-400/80 pl-5">
                                    <p className="text-sm leading-6 text-white/80">
                                        &ldquo;{quote.message}&rdquo;
                                    </p>

                                    <footer className="mt-2 text-xs font-medium text-white/50">
                                        {quote.author}
                                    </footer>
                                </blockquote>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="flex min-h-dvh items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
                <div className="w-full max-w-md">
                    <div className="mb-8 flex justify-center lg:hidden">
                        <Link
                            href={route('home')}
                            className="inline-flex items-center gap-3"
                        >
                            <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                                <AppLogoIcon className="size-6 fill-current text-white" />
                            </span>

                            <span className="text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                                {name}
                            </span>
                        </Link>
                    </div>

                    <div className="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xl shadow-slate-200/40 sm:p-7 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                        <div className="mb-7 flex flex-col gap-2">
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {title}
                            </h1>

                            <p className="text-sm leading-6 text-slate-500 dark:text-slate-400">
                                {description}
                            </p>
                        </div>

                        {children}
                    </div>

                    <p className="mt-6 text-center text-xs leading-5 text-slate-400 dark:text-slate-500">
                        © {new Date().getFullYear()} {name}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    );
}
