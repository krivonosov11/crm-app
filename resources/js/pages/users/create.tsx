import { Head } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/UserController';
import { UserForm } from '@/components/users/user-form';
import AppLayout from '@/layouts/app-layout';

export default function CreateUser() {
    return (
        <>
            <Head title="Add user" />
            <h1 className="mb-6 text-2xl font-semibold">Add user</h1>
            <UserForm
                form={store.form()}
                canAssignRole
                submitLabel="Create user"
            />
        </>
    );
}

CreateUser.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
