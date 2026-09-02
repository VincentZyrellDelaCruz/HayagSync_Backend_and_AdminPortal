import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import NotificationBell from './notification-bell';
import { usePage } from '@inertiajs/react';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { auth } = usePage().props as any;

    return (
        <header className="sticky top-0 z-50 shrink-0 border-b rounded-none md:rounded-t-xl border-slate-200/80 bg-white/95 shadow-sm backdrop-blur-md supports-backdrop-filter:bg-white/85 dark:border-slate-800/80 dark:bg-slate-950/95 dark:supports-backdrop-filter:bg-slate-950/85">
            <div className="flex min-h-16 items-center gap-3 px-3 sm:px-4 lg:px-6">
                <div className="flex min-w-0 flex-1 items-center gap-2">
                    <SidebarTrigger className="h-9 w-9 shrink-0 rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white" />

                    <div className="h-5 w-px shrink-0 bg-slate-200 dark:bg-slate-700" />

                    <div className="min-w-0 flex-1 overflow-hidden">
                        {breadcrumbs.length > 0 ? (
                            <div className="flex min-w-0 items-center">
                                <div className="min-w-0 max-w-full truncate">
                                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                                </div>
                            </div>
                        ) : (
                            <div className="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-300">
                                <span className="truncate">HayagSync</span>
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    <div className="relative">
                        <NotificationBell userId={auth.user?.id} />
                    </div>
                </div>
            </div>
        </header>
    );
}
