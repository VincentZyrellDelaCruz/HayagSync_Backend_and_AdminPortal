import { router } from '@inertiajs/react';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationMeta {
    from: number | null;
    to: number | null;
    total: number;
}

export interface PaginationProps {
    links: PaginationLink[];
    meta?: PaginationMeta;
    onNavigate?: (url: string) => void;
    preserveScroll?: boolean;
    className?: string;
}

const stripHtmlEntities = (label: string) => label.replace(/&laquo;|&raquo;/g, '').trim();

function Pagination({ links, meta, onNavigate, preserveScroll = true, className = '' }: PaginationProps) {
    // Nothing to page through, just "Previous" + one page + "Next".
    if (!links || links.length <= 3) return null;

    const go = (url: string | null) => {
        if (!url) return;
        if (onNavigate) {
            onNavigate(url);
        } else {
            router.get(url, {}, { preserveScroll, preserveState: true });
        }
    };

    const prevLink = links[0];
    const nextLink = links[links.length - 1];
    const pageLinks = links.slice(1, -1);
    const activeIndex = pageLinks.findIndex((link) => link.active);

    return (
        <div className={`flex flex-col sm:flex-row items-center justify-between gap-3 ${className}`}>
            {meta && (
                <p className="text-sm text-slate-500 order-2 sm:order-1">
                    Showing <span className="font-medium text-slate-700">{meta.from ?? 0}</span> to{' '}
                    <span className="font-medium text-slate-700">{meta.to ?? 0}</span> of{' '}
                    <span className="font-medium text-slate-700">{meta.total}</span> results
                </p>
            )}

            <nav className="flex items-center gap-1 order-1 sm:order-2" aria-label="Pagination">
                <button
                    type="button"
                    disabled={!prevLink.url}
                    onClick={() => go(prevLink.url)}
                    className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium transition ${
                        prevLink.url ? 'text-slate-600 hover:bg-slate-100' : 'text-slate-300 cursor-not-allowed'
                    }`}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                    <span className="hidden sm:inline">Previous</span>
                </button>

                <div className="hidden sm:flex items-center gap-1">
                    {pageLinks.map((link, index) =>
                        stripHtmlEntities(link.label) === '...' ? (
                            <span key={index} className="px-2 text-sm text-slate-400">
                                …
                            </span>
                        ) : (
                            <button
                                key={index}
                                type="button"
                                disabled={!link.url}
                                onClick={() => go(link.url)}
                                aria-current={link.active ? 'page' : undefined}
                                className={`min-w-9 px-3 py-1.5 rounded-lg text-sm font-medium transition ${
                                    link.active
                                        ? 'bg-slate-900 text-white'
                                        : link.url
                                          ? 'text-slate-600 hover:bg-slate-100'
                                          : 'text-slate-300 cursor-not-allowed'
                                }`}
                            >
                                {stripHtmlEntities(link.label)}
                            </button>
                        ),
                    )}
                </div>

                <span className="sm:hidden px-2 text-sm text-slate-500">
                    Page {activeIndex + 1} of {pageLinks.length}
                </span>

                <button
                    type="button"
                    disabled={!nextLink.url}
                    onClick={() => go(nextLink.url)}
                    className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium transition ${
                        nextLink.url ? 'text-slate-600 hover:bg-slate-100' : 'text-slate-300 cursor-not-allowed'
                    }`}
                >
                    <span className="hidden sm:inline">Next</span>
                    <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </nav>
        </div>
    );
}

export default Pagination;
