import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/UserController';
import { RoleBadge } from '@/components/role-badge';
import { StatusBadge } from '@/components/status-badge';
import { buttonClasses } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import {
    formatDate,
    roleLabels,
    roles,
    statusLabels,
    statuses,
} from '@/lib/users';
import { cn } from '@/lib/utils';
import type { Paginated, User } from '@/types';

type Props = {
    users: Paginated<User>;
    filters: {
        search: string;
        /** Role value as a string ('' when not filtering). */
        role: string;
        /** Status value as a string ('' when not filtering). */
        status: string;
    };
    can: {
        create: boolean;
        delete: boolean;
        filterByRole: boolean;
    };
};

export default function UsersIndex({ users, filters, can }: Props) {
    const currentUserId = usePage().props.auth.user?.id;
    const [search, setSearch] = useState(filters.search);
    const [role, setRole] = useState(filters.role);
    const [status, setStatus] = useState(filters.status);
    const isFirstRender = useRef(true);
    const title = can.filterByRole ? 'Users' : 'Candidates';

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                index.url(),
                {
                    search: search || undefined,
                    role: role || undefined,
                    status: status || undefined,
                },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, role, status]);

    const deleteUser = (user: User) => {
        if (confirm(`Delete ${user.name}? This cannot be undone.`)) {
            router.delete(destroy.url(user.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={title} />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-2xl font-semibold">{title}</h1>
                {can.create && (
                    <Link
                        href={create.url()}
                        className={buttonClasses('primary')}
                    >
                        Add user
                    </Link>
                )}
            </div>

            <div className="mb-4 flex flex-col gap-3 sm:flex-row">
                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search by name or email…"
                    className="sm:max-w-xs"
                />
                {can.filterByRole && (
                    <Select
                        value={role}
                        onChange={(event) => setRole(event.target.value)}
                        className="sm:w-44"
                    >
                        <option value="">All roles</option>
                        {roles.map((value) => (
                            <option key={value} value={String(value)}>
                                {roleLabels[value]}
                            </option>
                        ))}
                    </Select>
                )}
                <Select
                    value={status}
                    onChange={(event) => setStatus(event.target.value)}
                    className="sm:w-44"
                >
                    <option value="">All statuses</option>
                    {statuses.map((value) => (
                        <option key={value} value={String(value)}>
                            {statusLabels[value]}
                        </option>
                    ))}
                </Select>
            </div>

            <Card className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr className="text-left text-gray-500 dark:text-gray-400">
                            <th className="px-5 py-3 font-medium">Name</th>
                            <th className="px-5 py-3 font-medium">Email</th>
                            {can.filterByRole && (
                                <th className="px-5 py-3 font-medium">Role</th>
                            )}
                            <th className="px-5 py-3 font-medium">Status</th>
                            <th className="px-5 py-3 font-medium">CV</th>
                            <th className="px-5 py-3 font-medium">Created</th>
                            <th className="px-5 py-3" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100 dark:divide-white/5">
                        {users.data.map((user) => (
                            <tr key={user.id}>
                                <td className="px-5 py-3 font-medium whitespace-nowrap">
                                    <Link
                                        href={show.url(user.id)}
                                        className="hover:text-indigo-600 dark:hover:text-indigo-400"
                                    >
                                        {user.name}
                                    </Link>
                                </td>
                                <td className="px-5 py-3 text-gray-600 dark:text-gray-300">
                                    {user.email}
                                </td>
                                {can.filterByRole && (
                                    <td className="px-5 py-3">
                                        <RoleBadge role={user.role} />
                                    </td>
                                )}
                                <td className="px-5 py-3">
                                    <StatusBadge status={user.status} />
                                </td>
                                <td className="px-5 py-3 text-gray-600 dark:text-gray-300">
                                    {user.has_cv ? '✓' : '—'}
                                </td>
                                <td className="px-5 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                    {formatDate(user.created_at)}
                                </td>
                                <td className="px-5 py-3 text-right whitespace-nowrap">
                                    <Link
                                        href={edit.url(user.id)}
                                        className="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                    >
                                        Edit
                                    </Link>
                                    {can.delete &&
                                        user.id !== currentUserId && (
                                            <button
                                                type="button"
                                                onClick={() => deleteUser(user)}
                                                className="ml-4 font-medium text-red-600 hover:text-red-500 dark:text-red-400"
                                            >
                                                Delete
                                            </button>
                                        )}
                                </td>
                            </tr>
                        ))}
                        {users.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-5 py-10 text-center text-gray-500 dark:text-gray-400"
                                >
                                    No users found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </Card>

            {users.last_page > 1 && (
                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
                    <p className="text-gray-500 dark:text-gray-400">
                        Showing {users.from}–{users.to} of {users.total}
                    </p>
                    <nav className="flex flex-wrap gap-1">
                        {users.links.map((link, i) =>
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    preserveScroll
                                    preserveState
                                    className={cn(
                                        'rounded-md px-3 py-1.5',
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10',
                                    )}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    key={i}
                                    className="px-3 py-1.5 text-gray-400"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </nav>
                </div>
            )}
        </>
    );
}

UsersIndex.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
