import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import type { RouteFormDefinition } from '@/wayfinder';
import { Button, buttonClasses } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { InputError } from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import {
    Role,
    roleLabels,
    roles,
    Status,
    statusLabels,
    statuses,
} from '@/lib/users';
import { index as usersIndex } from '@/routes/users';
import type { User, UserRole } from '@/types';

type Props = {
    form: RouteFormDefinition<'post'>;
    user?: Pick<User, 'name' | 'email' | 'role' | 'status'>;
    canAssignRole: boolean;
    submitLabel: string;
};

export function UserForm({ form, user, canAssignRole, submitLabel }: Props) {
    const isEditing = user !== undefined;
    const [role, setRole] = useState<UserRole>(user?.role ?? Role.Candidate);

    return (
        <Card className="max-w-2xl p-6">
            <Form
                {...form}
                resetOnError={['password', 'password_confirmation']}
                className="space-y-5"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="space-y-1.5">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                defaultValue={user?.name}
                                autoComplete="off"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                defaultValue={user?.email}
                                autoComplete="off"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            {canAssignRole && (
                                <div className="space-y-1.5">
                                    <Label htmlFor="role">Role</Label>
                                    <Select
                                        id="role"
                                        name="role"
                                        value={role}
                                        onChange={(event) =>
                                            setRole(
                                                Number(
                                                    event.target.value,
                                                ) as UserRole,
                                            )
                                        }
                                    >
                                        {roles.map((value) => (
                                            <option key={value} value={value}>
                                                {roleLabels[value]}
                                            </option>
                                        ))}
                                    </Select>
                                    <InputError message={errors.role} />
                                </div>
                            )}

                            {role === Role.Candidate && (
                                <div className="space-y-1.5">
                                    <Label htmlFor="status">Status</Label>
                                    <Select
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            user?.status ?? Status.Created
                                        }
                                    >
                                        {statuses.map((value) => (
                                            <option key={value} value={value}>
                                                {statusLabels[value]}
                                            </option>
                                        ))}
                                    </Select>
                                    <InputError message={errors.status} />
                                </div>
                            )}
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required={!isEditing}
                                    autoComplete="new-password"
                                    placeholder={
                                        isEditing
                                            ? 'Leave blank to keep current'
                                            : undefined
                                    }
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    required={!isEditing}
                                    autoComplete="new-password"
                                />
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-3 pt-2">
                            <Link
                                href={usersIndex.url()}
                                className={buttonClasses('secondary')}
                            >
                                Cancel
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving…' : submitLabel}
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </Card>
    );
}
