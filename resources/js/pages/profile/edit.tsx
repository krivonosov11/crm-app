import { Form, Head } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/CandidateProfileController';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { InputError } from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { CandidateProfile } from '@/types';

type Props = {
    profile: CandidateProfile | null;
    cvUrl: string;
    maxCvKilobytes: number;
};

export default function EditProfile({ profile, cvUrl, maxCvKilobytes }: Props) {
    return (
        <>
            <Head title="My profile" />
            <h1 className="mb-2 text-2xl font-semibold">My profile</h1>
            <p className="mb-6 text-sm text-gray-500 dark:text-gray-400">
                Your summary and CV are visible to HR and administrators.
            </p>

            <Card className="max-w-2xl p-6">
                <Form
                    {...update.form()}
                    resetOnSuccess={['cv', 'remove_cv']}
                    className="space-y-6"
                >
                    {({ errors, processing, progress }) => (
                        <>
                            <div className="space-y-1.5">
                                <Label htmlFor="summary">Summary</Label>
                                <Textarea
                                    id="summary"
                                    name="summary"
                                    rows={6}
                                    maxLength={2000}
                                    defaultValue={profile?.summary ?? ''}
                                    placeholder="A few sentences about your experience and the role you are looking for…"
                                />
                                <InputError message={errors.summary} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="cv">CV</Label>
                                {profile?.has_cv && (
                                    <div className="flex flex-wrap items-center gap-4 text-sm">
                                        <a
                                            href={cvUrl}
                                            className="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                        >
                                            {profile.cv_name}
                                        </a>
                                        <label className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                            <input
                                                type="checkbox"
                                                name="remove_cv"
                                                value="1"
                                                className="size-4 rounded border-gray-300"
                                            />
                                            Remove current CV
                                        </label>
                                    </div>
                                )}
                                <input
                                    id="cv"
                                    type="file"
                                    name="cv"
                                    accept=".pdf,.doc,.docx"
                                    className="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300 dark:file:bg-indigo-400/10 dark:file:text-indigo-300"
                                />
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    PDF, DOC or DOCX, up to{' '}
                                    {Math.round(maxCvKilobytes / 1024)} MB.
                                    {profile?.has_cv &&
                                        ' Uploading a new file replaces the current CV.'}
                                </p>
                                {progress && (
                                    <progress
                                        value={progress.percentage}
                                        max="100"
                                        className="w-full"
                                    />
                                )}
                                <InputError message={errors.cv} />
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving…' : 'Save profile'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </Card>
        </>
    );
}

EditProfile.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
