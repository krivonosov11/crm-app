import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { RoleBadge } from '@/components/role-badge';
import { Role } from '@/lib/users';
import { cn } from '@/lib/utils';
import { dashboard, logout } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { index as usersIndex } from '@/routes/users';

export default function AppLayout({ children }: { children: ReactNode }) {
    const { props, url, flash } = usePage();
    const user = props.auth.user;

    const navigation = [
        { name: 'Dashboard', href: dashboard.url(), visible: true },
        {
            name: user?.role === Role.Hr ? 'Candidates' : 'Users',
            href: usersIndex.url(),
            visible: user?.role === Role.Admin || user?.role === Role.Hr,
        },
        {
            name: 'My profile',
            href: profileEdit.url(),
            visible: user?.role === Role.Candidate,
        },
    ].filter((item) => item.visible);

    return (
        <div className="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
            <header className="border-b border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                    <div className="flex items-center gap-8">
                        <Link
                            href={dashboard.url()}
                            className="text-lg font-semibold"
                        >
                            {props.name}
                        </Link>
                        <nav className="flex gap-1">
                            {navigation.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    prefetch
                                    className={cn(
                                        'rounded-md px-3 py-2 text-sm font-medium',
                                        url.startsWith(item.href)
                                            ? 'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white'
                                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5',
                                    )}
                                >
                                    {item.name}
                                </Link>
                            ))}
                        </nav>
                    </div>
                    {user && (
                        <div className="flex items-center gap-3">
                            <span className="hidden text-sm sm:inline">
                                {user.name}
                            </span>
                            <RoleBadge role={user.role} />
                            <Link
                                href={logout.url()}
                                method="post"
                                as="button"
                                className="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5"
                            >
                                Log out
                            </Link>
                        </div>
                    )}
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                {flash.success && (
                    <div className="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800 ring-1 ring-green-600/20 dark:bg-green-400/10 dark:text-green-300">
                        {flash.success}
                    </div>
                )}
                {children}
            </main>
        </div>
    );
}
