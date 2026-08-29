import { type ReactNode } from 'react';

export interface EmptyStateProps {
    icon: ReactNode;
    title: string;
    description?: string;
    className?: string;
}

export function EmptyState({ icon, title, description, className = '' }: EmptyStateProps) {
    return (
        <div className={`px-6 py-12 text-center ${className}`}>
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                {icon}
            </div>
            <h3 className="text-lg font-semibold text-slate-700">{title}</h3>
            {description && <p className="mt-2 text-sm text-slate-500">{description}</p>}
        </div>
    );
}

export default EmptyState;
