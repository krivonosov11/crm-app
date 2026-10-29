<?php

namespace App\Mcp\Tools;

use App\Enums\CandidateStatus;
use App\Mcp\Concerns\InteractsWithCandidates;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Name('list-candidates')]
#[Title('List Candidates')]
#[Description('Lists candidates, newest first. Optionally search by name or email and filter by application status. Results are paginated.')]
class ListCandidatesTool extends Tool
{
    use InteractsWithCandidates;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        if ($this->actingStaff($request) === null) {
            return $this->unauthorizedResponse();
        }

        /** @var array{search?: string|null, status?: string|null, page?: int|null, per_page?: int|null} $validated */
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(CandidateStatus::names())],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ], [
            'status.in' => 'Status must be one of: '.implode(', ', CandidateStatus::names()).'.',
        ]);

        $status = filled($validated['status'] ?? null)
            ? CandidateStatus::tryFromName((string) $validated['status'])
            : null;

        $candidates = User::query()
            ->candidates()
            ->with('candidateProfile')
            ->when($validated['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->latest()
            ->paginate(
                perPage: $validated['per_page'] ?? 20,
                page: $validated['page'] ?? 1,
            );

        return Response::json([
            'candidates' => $candidates->getCollection()
                ->map(fn (User $candidate) => $this->candidatePayload($candidate))
                ->all(),
            'pagination' => [
                'page' => $candidates->currentPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
                'last_page' => $candidates->lastPage(),
            ],
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Text to match against candidate name or email.'),

            'status' => $schema->string()
                ->enum(CandidateStatus::names())
                ->description('Only return candidates with this application status.'),

            'page' => $schema->integer()
                ->min(1)
                ->description('Page number, starting at 1.')
                ->default(1),

            'per_page' => $schema->integer()
                ->min(1)
                ->max(50)
                ->description('Number of candidates per page (max 50).')
                ->default(20),
        ];
    }
}
