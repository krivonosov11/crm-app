import { Head } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/UserController';
import { UserForm } from '@/components/users/user-form';
import AppLayout from '@/layouts/app-layout';
import type { User } from '@/types';

type Props = {
    user: Pick<User, 'id' | 'name' | 'email' | 'role' | 'status'>;
    canAssignRole: boolean;
};

export default function EditUser({ user, canAssignRole }: Props) {
    return (
        <>
            <Head title={`Edit ${user.name}`} />
            <h1 className="mb-6 text-2xl font-semibold">Edit user</h1>
            <UserForm
                form={update.form(user.id)}
                user={user}
                canAssignRole={canAssignRole}
                submitLabel="Save changes"
            />
        </>
    );
}

EditUser.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
