import { cn } from '@/lib/utils';
import { Role, roleLabels } from '@/lib/users';
import type { UserRole } from '@/types';

const roleStyles: Record<UserRole, string> = {
    [Role.Admin]:
        'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/30',
    [Role.Hr]:
        'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-400/10 dark:text-indigo-300 dark:ring-indigo-400/30',
    [Role.Candidate]:
        'bg-gray-50 text-gray-700 ring-gray-500/20 dark:bg-white/5 dark:text-gray-300 dark:ring-white/15',
};

export function RoleBadge({ role }: { role: UserRole }) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                roleStyles[role],
            )}
        >
            {roleLabels[role]}
        </span>
    );
}
