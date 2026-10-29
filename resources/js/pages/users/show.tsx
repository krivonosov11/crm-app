import { Head, Link } from '@inertiajs/react';
import { edit, index } from '@/actions/App/Http/Controllers/UserController';
import { CandidateProfileCard } from '@/components/candidate-profile-card';
import { RoleBadge } from '@/components/role-badge';
import { StatusBadge } from '@/components/status-badge';
import { buttonClasses } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDate, Role } from '@/lib/users';
import type { CandidateProfile, User } from '@/types';

type Props = {
    user: User;
    profile: CandidateProfile | null;
    cvUrl: string | null;
    can: {
        update: boolean;
    };
};

export default function ShowUser({ user, profile, cvUrl, can }: Props) {
    const isCandidate = user.role === Role.Candidate;

    return (
        <>
            <Head title={user.name} />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <Link
                        href={index.url()}
                        className="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
                    >
                        ← Back to list
                    </Link>
                    <h1 className="mt-1 text-2xl font-semibold">{user.name}</h1>
                </div>
                {can.update && (
                    <Link
                        href={edit.url(user.id)}
                        className={buttonClasses('secondary')}
                    >
                        Edit
                    </Link>
                )}
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="p-6 lg:col-span-1">
                    <dl className="space-y-3 text-sm">
                        <div className="flex justify-between gap-4">
                            <dt className="text-gray-500 dark:text-gray-400">
                                Email
                            </dt>
                            <dd className="truncate">{user.email}</dd>
                        </div>
                        <div className="flex justify-between gap-4">
                            <dt className="text-gray-500 dark:text-gray-400">
                                Role
                            </dt>
                            <dd>
                                <RoleBadge role={user.role} />
                            </dd>
                        </div>
                        {isCandidate && (
                            <div className="flex justify-between gap-4">
                                <dt className="text-gray-500 dark:text-gray-400">
                                    Status
                                </dt>
                                <dd>
                                    <StatusBadge status={user.status} />
                                </dd>
                            </div>
                        )}
                        <div className="flex justify-between gap-4">
                            <dt className="text-gray-500 dark:text-gray-400">
                                Created
                            </dt>
                            <dd>{formatDate(user.created_at)}</dd>
                        </div>
                    </dl>
                </Card>

                {isCandidate && (
                    <div className="lg:col-span-2">
                        <CandidateProfileCard
                            profile={profile}
                            cvUrl={cvUrl}
                            emptyMessage="This candidate has not filled in their profile yet."
                        />
                    </div>
                )}
            </div>
        </>
    );
}

ShowUser.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
