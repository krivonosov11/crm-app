<?php

namespace App\Mcp\Concerns;

use App\Enums\CandidateStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait InteractsWithCandidates
{
    /**
     * Get the HR / admin user the MCP request acts as, if they may access candidates.
     */
    protected function actingStaff(Request $request): ?User
    {
        $user = $request->user();

        if (! $user instanceof User || Gate::forUser($user)->denies('viewAny', User::class)) {
            return null;
        }

        return $user;
    }

    /**
     * The error returned when the server has no authorized acting user.
     */
    protected function unauthorizedResponse(): Response
    {
        return Response::error(
            'Access denied. The MCP server must act as an HR or admin account: set MCP_USER_EMAIL in the MCP client configuration.'
        );
    }

    /**
     * Find a candidate by ID.
     */
    protected function findCandidate(int $id): ?User
    {
        return User::query()->candidates()->with('candidateProfile')->find($id);
    }

    /**
     * Serialize a candidate for MCP responses.
     *
     * @return array<string, mixed>
     */
    protected function candidatePayload(User $candidate, bool $withProfile = false): array
    {
        $payload = [
            'id' => $candidate->id,
            'name' => $candidate->name,
            'email' => $candidate->email,
            'status' => $this->statusPayload($candidate->status),
            'has_cv' => (bool) $candidate->candidateProfile?->hasCv(),
            'created_at' => $candidate->created_at?->toIso8601String(),
        ];

        if ($withProfile) {
            $profile = $candidate->candidateProfile;

            $payload['profile'] = [
                'summary' => $profile?->summary,
                'cv_file_name' => $profile?->cv_original_name,
                'cv_download_url' => $profile?->hasCv() ? route('users.cv', $candidate) : null,
                'updated_at' => $profile?->updated_at?->toIso8601String(),
            ];
            $payload['web_url'] = route('users.show', $candidate);
        }

        return $payload;
    }

    /**
     * Summarize the hiring pipeline: totals, counts per status and CV uploads.
     *
     * @return array{total: int, by_status: list<array{value: int, name: string, label: string, count: int}>, with_cv: int}
     */
    protected function pipelineStats(): array
    {
        $counts = User::query()
            ->candidates()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total' => (int) $counts->sum(),
            'by_status' => array_map(fn (CandidateStatus $status) => [
                'value' => $status->value,
                'name' => strtolower($status->name),
                'label' => $status->label(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ], CandidateStatus::cases()),
            'with_cv' => User::query()
                ->candidates()
                ->whereHas('candidateProfile', fn (Builder $query) => $query->whereNotNull('cv_path'))
                ->count(),
        ];
    }

    /**
     * Encode data as pretty JSON for embedding in prompt text.
     */
    protected function toPromptJson(mixed $data): string
    {
        return (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Serialize a candidate status.
     *
     * @return array{value: int, name: string, label: string}|null
     */
    protected function statusPayload(?CandidateStatus $status): ?array
    {
        return $status === null ? null : [
            'value' => $status->value,
            'name' => strtolower($status->name),
            'label' => $status->label(),
        ];
    }
}
