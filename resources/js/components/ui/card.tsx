import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export function Card({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            className={cn(
                'rounded-lg bg-white shadow-xs ring-1 ring-gray-900/5 dark:bg-white/5 dark:ring-white/10',
                className,
            )}
            {...props}
        />
    );
}
