import { type ReactNode } from 'react';

export interface InfoFieldProps {
    label: string;
    value: ReactNode;
    icon?: ReactNode;
}

export function InfoField({ label, value, icon }: InfoFieldProps) {
    return (
        <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1 flex items-center gap-1.5">
                {icon}
                {label}
            </p>
            <p className="text-sm text-slate-800">{value}</p>
        </div>
    );
}

export default InfoField;
