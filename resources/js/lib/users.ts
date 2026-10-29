import type { CandidateStatus, UserRole } from '@/types';

/**
 * Mirrors the integer-backed App\Enums\UserRole enum.
 */
export const Role = {
    Admin: 0,
    Hr: 1,
    Candidate: 2,
} as const satisfies Record<string, UserRole>;

export const roleLabels: Record<UserRole, string> = {
    [Role.Admin]: 'Admin',
    [Role.Hr]: 'HR',
    [Role.Candidate]: 'Candidate',
};

export const roles = Object.values(Role);

/**
 * Mirrors the integer-backed App\Enums\CandidateStatus enum.
 */
export const Status = {
    Created: 0,
    Processing: 1,
    Approved: 2,
    Rejected: 3,
} as const satisfies Record<string, CandidateStatus>;

export const statusLabels: Record<CandidateStatus, string> = {
    [Status.Created]: 'Created',
    [Status.Processing]: 'Processing',
    [Status.Approved]: 'Approved',
    [Status.Rejected]: 'Rejected',
};

export const statuses = Object.values(Status);

export const formatDate = (value?: string | null) =>
    value ? new Date(value).toLocaleDateString() : '—';
