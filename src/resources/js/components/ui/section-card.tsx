import { type ReactNode } from 'react';

export interface SectionCardProps {
    title: string;
    icon?: ReactNode;
    right?: ReactNode;
    children: ReactNode;
    className?: string;
}

export function SectionCard({ title, icon, right, children, className = '' }: SectionCardProps) {
    return (
        <div className={`bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden ${className}`}>
            <div className="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between gap-2">
                <h2 className="text-sm font-semibold text-slate-700 flex items-center gap-2">
                    {icon}
                    {title}
                </h2>
                {right}
            </div>
            {children}
        </div>
    );
}

export default SectionCard;
