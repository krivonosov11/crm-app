<?php

namespace App\Http\Controllers;

use App\Enums\CandidateStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a paginated, filterable list of users.
     *
     * HR users only ever see candidates.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        /** @var User $currentUser */
        $currentUser = $request->user();

        /** @var array{search?: string|null, role?: string|null, status?: string|null} $filters */
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::enum(CandidateStatus::class)],
        ]);

        $role = $filters['role'] ?? null;
        $status = $filters['status'] ?? null;

        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'created_at'])
            ->withExists(['candidateProfile as has_cv' => fn (Builder $query) => $query->whereNotNull('cv_path')])
            ->when(! $currentUser->isAdmin(), fn (Builder $query) => $query->candidates())
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->when(filled($role), fn (Builder $query) => $query->where('role', (int) $role))
            ->when(filled($status), fn (Builder $query) => $query->where('status', (int) $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role' => (string) $role,
                'status' => (string) $status,
            ],
            'can' => [
                'create' => $currentUser->can('create', User::class),
                'delete' => $currentUser->isAdmin(),
                'filterByRole' => $currentUser->can('assignRole', User::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('users/create');
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        Inertia::flash('success', 'User created.');

        return to_route('users.index');
    }

    /**
     * Display the given user, including their candidate profile.
     */
    public function show(Request $request, User $user): Response
    {
        Gate::authorize('view', $user);

        $profile = $user->candidateProfile;

        return Inertia::render('users/show', [
            'user' => $user->only('id', 'name', 'email', 'role', 'status', 'created_at'),
            'profile' => $profile?->toPublicArray(),
            'cvUrl' => $profile?->hasCv() ? route('users.cv', $user) : null,
            'can' => [
                'update' => $request->user()?->can('update', $user) ?? false,
            ],
        ]);
    }

    /**
     * Show the form for editing the given user.
     */
    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('users/edit', [
            'user' => $user->only('id', 'name', 'email', 'role', 'status'),
            'canAssignRole' => $request->user()?->can('assignRole', User::class) ?? false,
        ]);
    }

    /**
     * Update the given user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update(array_filter(
            $request->validated(),
            fn (mixed $value, string $key) => $key !== 'password' || filled($value),
            ARRAY_FILTER_USE_BOTH,
        ));

        Inertia::flash('success', 'User updated.');

        return to_route('users.index');
    }

    /**
     * Delete the given user.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        Inertia::flash('success', 'User deleted.');

        return to_route('users.index');
    }
}
