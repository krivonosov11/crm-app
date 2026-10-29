import { Head, Link } from '@inertiajs/react';
import { RoleBadge } from '@/components/role-badge';
import { StatusBadge } from '@/components/status-badge';
import { CandidateProfileCard } from '@/components/candidate-profile-card';
import { buttonClasses } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDate, Role } from '@/lib/users';
import { edit as profileEdit } from '@/routes/profile';
import { cv, show, index as usersIndex } from '@/routes/users';
import type { CandidateProfile, User } from '@/types';

type Stat = {
    label: string;
    value: number;
};

type Props = {
    stats?: Stat[];
    recentUsers?: User[];
    profile?: User;
    candidateProfile?: CandidateProfile | null;
};

export default function Dashboard({
    stats,
    recentUsers,
    profile,
    candidateProfile = null,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <h1 className="mb-6 text-2xl font-semibold">Dashboard</h1>

            {stats && recentUsers && (
                <StaffDashboard stats={stats} recentUsers={recentUsers} />
            )}
            {profile && (
                <CandidateDashboard
                    profile={profile}
                    candidateProfile={candidateProfile}
                />
            )}
        </>
    );
}

Dashboard.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;

function StaffDashboard({
    stats,
    recentUsers,
}: {
    stats: Stat[];
    recentUsers: User[];
}) {
    return (
        <div className="space-y-8">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                {stats.map((stat) => (
                    <Card key={stat.label} className="p-5">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            {stat.label}
                        </p>
                        <p className="mt-2 text-3xl font-semibold tabular-nums">
                            {stat.value}
                        </p>
                    </Card>
                ))}
            </div>

            <Card>
                <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-white/10">
                    <h2 className="font-semibold">Recently added</h2>
                    <Link
                        href={usersIndex.url()}
                        className="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                    >
                        View all
                    </Link>
                </div>
                <ul className="divide-y divide-gray-100 dark:divide-white/5">
                    {recentUsers.map((user) => (
                        <li
                            key={user.id}
                            className="flex items-center justify-between gap-4 px-5 py-3"
                        >
                            <div className="min-w-0">
                                <Link
                                    href={show.url(user.id)}
                                    className="block truncate text-sm font-medium hover:text-indigo-600 dark:hover:text-indigo-400"
                                >
                                    {user.name}
                                </Link>
                                <p className="truncate text-sm text-gray-500 dark:text-gray-400">
                                    {user.email}
                                </p>
                            </div>
                            <div className="flex items-center gap-3">
                                <RoleBadge role={user.role} />
                                {user.role === Role.Candidate && (
                                    <StatusBadge status={user.status} />
                                )}
                                <span className="hidden text-sm text-gray-500 sm:inline dark:text-gray-400">
                                    {formatDate(user.created_at)}
                                </span>
                            </div>
                        </li>
                    ))}
                </ul>
            </Card>
        </div>
    );
}

function CandidateDashboard({
    profile,
    candidateProfile,
}: {
    profile: User;
    candidateProfile: CandidateProfile | null;
}) {
    return (
        <div className="grid gap-6 lg:grid-cols-3">
            <Card className="p-6">
                <h2 className="text-lg font-semibold">
                    Welcome, {profile.name}
                </h2>
                <dl className="mt-4 space-y-3 text-sm">
                    <div className="flex justify-between gap-4">
                        <dt className="text-gray-500 dark:text-gray-400">
                            Email
                        </dt>
                        <dd>{profile.email}</dd>
                    </div>
                    <div className="flex justify-between gap-4">
                        <dt className="text-gray-500 dark:text-gray-400">
                            Role
                        </dt>
                        <dd>
                            <RoleBadge role={profile.role} />
                        </dd>
                    </div>
                    <div className="flex justify-between gap-4">
                        <dt className="text-gray-500 dark:text-gray-400">
                            Application status
                        </dt>
                        <dd>
                            <StatusBadge status={profile.status} />
                        </dd>
                    </div>
                    <div className="flex justify-between gap-4">
                        <dt className="text-gray-500 dark:text-gray-400">
                            Member since
                        </dt>
                        <dd>{formatDate(profile.created_at)}</dd>
                    </div>
                </dl>
            </Card>

            <div className="space-y-3 lg:col-span-2">
                <CandidateProfileCard
                    profile={candidateProfile}
                    cvUrl={cv.url(profile.id)}
                    emptyMessage="Add a short summary and upload your CV so HR can review your application."
                />
                <Link
                    href={profileEdit.url()}
                    className={buttonClasses('secondary')}
                >
                    {candidateProfile ? 'Update profile' : 'Complete profile'}
                </Link>
            </div>
        </div>
    );
}
