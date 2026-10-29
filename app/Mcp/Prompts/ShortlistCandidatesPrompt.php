<?php

namespace App\Mcp\Prompts;

use App\Enums\CandidateStatus;
use App\Mcp\Concerns\InteractsWithCandidates;
use App\Models\User;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('shortlist-candidates')]
#[Title('Shortlist Candidates')]
#[Description('Rank candidates against a set of role requirements and propose a shortlist.')]
class ShortlistCandidatesPrompt extends Prompt
{
    use InteractsWithCandidates;

    /**
     * The maximum number of candidates embedded in the prompt.
     */
    protected const int MAX_CANDIDATES = 30;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        if ($this->actingStaff($request) === null) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'requirements' => ['required', 'string', 'max:2000'],
            'status' => ['nullable', 'string', Rule::in(CandidateStatus::names())],
        ], [
            'status.in' => 'Status must be one of: '.implode(', ', CandidateStatus::names()).'.',
        ]);

        $status = filled($validated['status'] ?? null)
            ? CandidateStatus::tryFromName((string) $validated['status'])
            : null;

        $candidates = User::query()
            ->candidates()
            ->with('candidateProfile')
            ->when(
                $status !== null,
                fn ($query) => $query->where('status', $status),
                fn ($query) => $query->whereIn('status', [CandidateStatus::Created, CandidateStatus::Processing]),
            )
            ->latest()
            ->limit(self::MAX_CANDIDATES)
            ->get();

        $scope = $status !== null
            ? "candidates with status \"{$status->label()}\""
            : 'open candidates (status Created or Processing)';

        if ($candidates->isEmpty()) {
            return Response::text("There are no {$scope} in the CRM right now, so there is nobody to shortlist. Tell the HR specialist this and suggest checking another status.");
        }

        $data = $this->toPromptJson($candidates->map(fn (User $candidate) => [
            'id' => $candidate->id,
            'name' => $candidate->name,
            'status' => strtolower((string) $candidate->status?->name),
            'has_cv' => (bool) $candidate->candidateProfile?->hasCv(),
            'summary' => $candidate->candidateProfile?->summary,
        ])->all());

        $limitNote = $candidates->count() === self::MAX_CANDIDATES
            ? 'Only the '.self::MAX_CANDIDATES.' most recent candidates are included. Mention that older candidates were not considered.'
            : '';

        return Response::text(<<<TEXT
            You are helping an HR specialist build a shortlist from our CRM.

            Role requirements:
            """
            {$validated['requirements']}
            """

            These are the {$scope} ({$candidates->count()} in total). {$limitNote}
            ```json
            {$data}
            ```

            Please:
            1. Score each candidate from 1 to 5 against the requirements, with a one-line reason, using only their summary.
            2. Show the result as a table: rank, name, id, score, reason, and whether a CV is on file.
            3. Recommend a shortlist of the top candidates (up to 5) and explain the cut-off.
            4. List candidates whose summary is empty or too thin to judge, so HR can follow up.

            Don't invent qualifications the summaries don't mention.
            If the HR specialist wants to move shortlisted candidates forward, you can set them to "processing" with the update-candidate-status tool, but only after they confirm.
            TEXT);
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'requirements',
                description: 'The role and its requirements, e.g. "Senior Laravel developer, 5+ years, React experience".',
                required: true,
            ),
            new Argument(
                name: 'status',
                description: 'Optional: only consider candidates with this status (created, processing, approved, rejected). Defaults to created and processing.',
                required: false,
            ),
        ];
    }
}
