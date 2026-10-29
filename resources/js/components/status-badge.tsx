import { cn } from '@/lib/utils';
import { Status, statusLabels } from '@/lib/users';
import type { CandidateStatus } from '@/types';

const statusStyles: Record<CandidateStatus, string> = {
    [Status.Created]:
        'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/30',
    [Status.Processing]:
        'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-300 dark:ring-yellow-400/30',
    [Status.Approved]:
        'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/30',
    [Status.Rejected]:
        'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-300 dark:ring-red-400/30',
};

export function StatusBadge({ status }: { status?: CandidateStatus | null }) {
    if (status === null || status === undefined) {
        return <span className="text-gray-400">—</span>;
    }

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                statusStyles[status],
            )}
        >
            {statusLabels[status]}
        </span>
    );
}
