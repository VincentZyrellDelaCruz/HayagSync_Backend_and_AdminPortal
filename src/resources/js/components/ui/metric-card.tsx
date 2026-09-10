import { UserRound } from "lucide-react";

export default function MetricCard({ label, value, description, icon: Icon, valueClassName = 'text-slate-900' }:
    { label: string; value: number | string; description?: string; icon: typeof UserRound; valueClassName?: string }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-medium text-slate-500">
                        {label}
                    </p>

                    <p className={`mt-2 text-2xl font-bold tracking-tight ${valueClassName}`}>
                        {value}
                    </p>

                    <p className="mt-1 text-xs leading-4 text-slate-500">
                        {description}
                    </p>
                </div>

                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <Icon className="h-5 w-5" />
                </div>
            </div>
        </div>
    );
}
