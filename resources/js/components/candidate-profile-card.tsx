import { Card } from '@/components/ui/card';
import { formatDate } from '@/lib/users';
import type { CandidateProfile } from '@/types';

type Props = {
    profile: CandidateProfile | null;
    cvUrl: string | null;
    emptyMessage: string;
};

export function CandidateProfileCard({ profile, cvUrl, emptyMessage }: Props) {
    return (
        <Card className="p-6">
            <h2 className="text-lg font-semibold">Summary & CV</h2>

            {profile ? (
                <div className="mt-4 space-y-4 text-sm">
                    <p className="whitespace-pre-line text-gray-700 dark:text-gray-300">
                        {profile.summary || (
                            <span className="text-gray-400">
                                No summary provided.
                            </span>
                        )}
                    </p>
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-white/5">
                        {profile.has_cv && cvUrl ? (
                            <a
                                href={cvUrl}
                                className="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                Download CV ({profile.cv_name})
                            </a>
                        ) : (
                            <span className="text-gray-400">
                                No CV uploaded.
                            </span>
                        )}
                        <span className="text-gray-500 dark:text-gray-400">
                            Updated {formatDate(profile.updated_at)}
                        </span>
                    </div>
                </div>
            ) : (
                <p className="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    {emptyMessage}
                </p>
            )}
        </Card>
    );
}
