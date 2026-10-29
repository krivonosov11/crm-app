<?php

namespace App\Http\Controllers;

use App\Enums\CandidateStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The user columns exposed to the dashboard.
     *
     * @var list<string>
     */
    protected const array COLUMNS = ['id', 'name', 'email', 'role', 'status', 'created_at'];

    /**
     * Show the dashboard for the authenticated user.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isCandidate()) {
            return Inertia::render('dashboard', [
                'profile' => $user->only(self::COLUMNS),
                'candidateProfile' => $user->candidateProfile?->toPublicArray(),
            ]);
        }

        return Inertia::render('dashboard', $user->isAdmin()
            ? $this->adminData()
            : $this->hrData());
    }

    /**
     * Get the dashboard data for admins: users per role.
     *
     * @return array{stats: list<array{label: string, value: int}>, recentUsers: mixed}
     */
    protected function adminData(): array
    {
        $counts = User::query()
            ->selectRaw('role, count(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        return [
            'stats' => [
                ['label' => 'Total users', 'value' => (int) $counts->sum()],
                ...array_map(fn (UserRole $role) => [
                    'label' => $role->label(),
                    'value' => (int) ($counts[$role->value] ?? 0),
                ], UserRole::cases()),
            ],
            'recentUsers' => User::query()->latest()->limit(5)->get(self::COLUMNS),
        ];
    }

    /**
     * Get the dashboard data for HR: candidates per status.
     *
     * @return array{stats: list<array{label: string, value: int}>, recentUsers: mixed}
     */
    protected function hrData(): array
    {
        $counts = User::query()
            ->candidates()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'stats' => [
                ['label' => 'Candidates', 'value' => (int) $counts->sum()],
                ...array_map(fn (CandidateStatus $status) => [
                    'label' => $status->label(),
                    'value' => (int) ($counts[$status->value] ?? 0),
                ], CandidateStatus::cases()),
            ],
            'recentUsers' => User::query()->candidates()->latest()->limit(5)->get(self::COLUMNS),
        ];
    }
}
