import { router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { Bell, CheckCheck, ExternalLink, FileText, ShieldAlert, UserRound } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface NotificationItem {
    id: number;
    type: string;
    title: string;
    message: string | null;
    priority: string;
    action_url?: string | null;
    data?: Record<string, unknown>;
    is_read: boolean;
    created_at: string;
    expires_at?: string | null;
}

interface NotificationResponse {
    notifications: NotificationItem[];
    unread_count: number;
}

const typeIcon = (type: string) => {
    if (type === 'security_alert' || type === 'security') {
        return <ShieldAlert className="h-4 w-4 text-red-600" />;
    }

    if (type === 'report_submitted' || type === 'report_escalated') {
        return <FileText className="h-4 w-4 text-blue-600" />;
    }

    return <UserRound className="h-4 w-4 text-slate-500" />;
};

const priorityClass = (priority: string) => {
    switch (priority.toLowerCase()) {
        case 'critical':
            return 'bg-red-100 text-red-700';
        case 'high':
            return 'bg-orange-100 text-orange-700';
        case 'medium':
            return 'bg-amber-100 text-amber-700';
        default:
            return 'bg-blue-100 text-blue-700';
    }
};

const relativeTime = (value: string) => {
    const date = new Date(value);
    const seconds = Math.floor(
        (Date.now() - date.getTime()) / 1000,
    );

    if (seconds < 60) return 'Just now';

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;

    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;

    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
};

export default function NotificationBell({
    userId,
}: {
    userId?: string;
}) {
    const [notifications, setNotifications] = useState<NotificationItem[]>([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [isOpen, setIsOpen] = useState(false);
    const [toast, setToast] = useState<NotificationItem | null>(null);

    const containerRef = useRef<HTMLDivElement | null>(null);

    const loadNotifications = async () => {
        try {
            const response = await fetch(
                route('notifications.index'),
                {
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                },
            );

            if (!response.ok) return;

            const data =
                (await response.json()) as NotificationResponse;

            setNotifications(data.notifications);
            setUnreadCount(data.unread_count);
        } catch {
            //
        }
    };

    useEffect(() => {
        loadNotifications();
    }, []);

    useEcho<NotificationItem>(
        userId ? `user.${userId}` : 'user.invalid',
        '.notification.created',
        (notification) => {
            setNotifications((previous) =>
                [
                    notification,
                    ...previous.filter(
                        (item) =>
                            item.id !== notification.id,
                    ),
                ].slice(0, 20),
            );

            setUnreadCount(
                (previous) => previous + 1,
            );

            setToast(notification);
        },
    );

    useEffect(() => {
        if (!toast) return;

        const timeout = window.setTimeout(() => {
            setToast(null);
        }, 6000);

        return () => window.clearTimeout(timeout);
    }, [toast]);

    useEffect(() => {
        const handleClickOutside = (
            event: MouseEvent,
        ) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(
                    event.target as Node,
                )
            ) {
                setIsOpen(false);
            }
        };

        document.addEventListener(
            'mousedown',
            handleClickOutside,
        );

        return () => {
            document.removeEventListener(
                'mousedown',
                handleClickOutside,
            );
        };
    }, []);

    const markRead = async (
        notification: NotificationItem,
    ) => {
        if (notification.is_read) return;

        setNotifications((previous) =>
            previous.map((item) =>
                item.id === notification.id
                    ? {
                        ...item,
                        is_read: true,
                    }
                    : item,
            ),
        );

        setUnreadCount((previous) =>
            Math.max(0, previous - 1),
        );

        try {
            await fetch(
                route(
                    'notifications.read',
                    notification.id,
                ),
                {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            )?.getAttribute(
                                'content',
                            ) ?? '',
                    },
                    credentials: 'same-origin',
                },
            );
        } catch {
            //
        }
    };

    const openNotification = async (
        notification: NotificationItem,
    ) => {
        await markRead(notification);

        setIsOpen(false);
        setToast(null);

        if (notification.action_url) {
            router.visit(
                notification.action_url,
                {
                    preserveScroll: false,
                },
            );
        }
    };

    const markAllRead = async () => {
        setNotifications((previous) =>
            previous.map((item) => ({
                ...item,
                is_read: true,
            })),
        );

        setUnreadCount(0);

        try {
            await fetch(
                route('notifications.read-all'),
                {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            )?.getAttribute(
                                'content',
                            ) ?? '',
                    },
                    credentials: 'same-origin',
                },
            );
        } catch {
            //
        }
    };

    return (
        <>
            <div
                ref={containerRef}
                className="relative"
            >
                <button
                    type="button"
                    onClick={() =>
                        setIsOpen(
                            (previous) =>
                                !previous,
                        )
                    }
                    className="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
                    aria-label="Notifications"
                    aria-expanded={isOpen}
                >
                    <Bell className="h-5 w-5" />

                    {unreadCount > 0 && (
                        <span className="absolute -right-1 -top-1 flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white ring-2 ring-white">
                            {unreadCount > 99
                                ? '99+'
                                : unreadCount}
                        </span>
                    )}
                </button>

                {isOpen && (
                    <div className="absolute right-0 z-50 mt-2 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                        <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3.5">
                            <div>
                                <h3 className="text-sm font-semibold text-slate-800">
                                    Notifications
                                </h3>

                                <p className="text-xs text-slate-400">
                                    {unreadCount} unread
                                </p>
                            </div>

                            {unreadCount > 0 && (
                                <button
                                    type="button"
                                    onClick={markAllRead}
                                    className="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                                >
                                    <CheckCheck className="h-3.5 w-3.5" />
                                    Mark all read
                                </button>
                            )}
                        </div>

                        <div className="max-h-112 overflow-y-auto">
                            {notifications.length > 0 ? (
                                notifications.map(
                                    (notification) => (
                                        <button
                                            key={notification.id}
                                            type="button"
                                            onClick={() =>
                                                openNotification(
                                                    notification,
                                                )
                                            }
                                            className={`flex w-full gap-3 border-b border-slate-100 px-4 py-3.5 text-left transition last:border-b-0 hover:bg-slate-50 ${
                                                notification.is_read
                                                    ? 'bg-white'
                                                    : 'bg-blue-50/50'
                                            }`}
                                        >
                                            <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                                                {typeIcon(
                                                    notification.type,
                                                )}
                                            </div>

                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-start justify-between gap-3">
                                                    <p
                                                        className={`line-clamp-2 text-sm ${
                                                            notification.is_read
                                                                ? 'font-medium text-slate-700'
                                                                : 'font-semibold text-slate-900'
                                                        }`}
                                                    >
                                                        {notification.title}
                                                    </p>

                                                    {!notification.is_read && (
                                                        <span className="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-600" />
                                                    )}
                                                </div>

                                                {notification.message && (
                                                    <p className="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">
                                                        {notification.message}
                                                    </p>
                                                )}

                                                <div className="mt-2 flex flex-wrap items-center gap-2">
                                                    <span className="text-[11px] text-slate-400">
                                                        {relativeTime(
                                                            notification.created_at,
                                                        )}
                                                    </span>

                                                    {notification.priority !== 'normal' && (
                                                        <span
                                                            className={`rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize ${priorityClass(
                                                                notification.priority,
                                                            )}`}
                                                        >
                                                            {
                                                                notification.priority
                                                            }
                                                        </span>
                                                    )}

                                                    {notification.action_url && (
                                                        <ExternalLink className="ml-auto h-3.5 w-3.5 text-slate-300" />
                                                    )}
                                                </div>
                                            </div>
                                        </button>
                                    ),
                                )
                            ) : (
                                <div className="px-5 py-12 text-center">
                                    <div className="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100">
                                        <Bell className="h-5 w-5 text-slate-400" />
                                    </div>

                                    <p className="text-sm font-medium text-slate-700">
                                        You're all caught up
                                    </p>

                                    <p className="mt-1 text-xs text-slate-400">
                                        New system updates will
                                        appear here.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {toast && (
                <div className="fixed right-4 top-4 z-100 w-[min(24rem,calc(100vw-2rem))]">
                    <button
                        type="button"
                        onClick={() =>
                            openNotification(toast)
                        }
                        className="flex w-full items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-2xl transition hover:bg-slate-50"
                    >
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                            {typeIcon(toast.type)}
                        </div>

                        <div className="min-w-0 flex-1">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm font-semibold text-slate-800">
                                    {toast.title}
                                </p>

                                <span className="shrink-0 text-[11px] text-slate-400">
                                    Now
                                </span>
                            </div>

                            {toast.message && (
                                <p className="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">
                                    {toast.message}
                                </p>
                            )}

                            <p className="mt-2 text-xs font-semibold text-blue-600">
                                View details
                            </p>
                        </div>
                    </button>
                </div>
            )}
        </>
    );
}
