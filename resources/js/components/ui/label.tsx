import type { LabelHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export function Label({ className, ...props }: LabelHTMLAttributes<HTMLLabelElement>) {
    return (
        <label
            className={cn('block text-sm font-medium text-gray-900 dark:text-gray-100', className)}
            {...props}
        />
    );
}
