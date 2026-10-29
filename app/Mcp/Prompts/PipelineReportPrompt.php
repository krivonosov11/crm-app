<?php

namespace App\Mcp\Prompts;

use App\Enums\CandidateStatus;
use App\Mcp\Concerns\InteractsWithCandidates;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('pipeline-report')]
#[Title('Pipeline Report')]
#[Description('Write a hiring pipeline report: status breakdown, candidates waiting for review, missing CVs and suggested next actions.')]
class PipelineReportPrompt extends Prompt
{
    use InteractsWithCandidates;

    /**
     * How many days a candidate may sit in "Created" before they count as waiting too long.
     */
    protected const int STALE_AFTER_DAYS = 7;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        if ($this->actingStaff($request) === null) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'audience' => ['nullable', 'string', 'max:200'],
        ]);

        $waitingForReview = User::query()
            ->candidates()
            ->where('status', CandidateStatus::Created)
            ->where('created_at', '<=', now()->subDays(self::STALE_AFTER_DAYS))
            ->oldest()
            ->limit(20)
            ->get(['id', 'name', 'created_at'])
            ->map(fn (User $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'days_waiting' => (int) $candidate->created_at?->diffInDays(now()),
            ]);

        $openWithoutCv = User::query()
            ->candidates()
            ->whereIn('status', [CandidateStatus::Created, CandidateStatus::Processing])
            ->whereDoesntHave('candidateProfile', fn ($query) => $query->whereNotNull('cv_path'))
            ->count();

        $data = $this->toPromptJson([
            'generated_at' => now()->toIso8601String(),
            'pipeline' => $this->pipelineStats(),
            'open_candidates_without_cv' => $openWithoutCv,
            'waiting_for_review_over_'.self::STALE_AFTER_DAYS.'_days' => $waitingForReview->all(),
        ]);

        $audience = $validated['audience'] ?? 'the HR team';

        return Response::text(<<<TEXT
            Write a short hiring pipeline report for {$audience}, using this live data from our CRM:
            ```json
            {$data}
            ```

            Structure:
            1. **Headline**: one sentence on the overall state of the pipeline.
            2. **Status breakdown**: a small table with each status, its count and its share of the total.
            3. **Needs attention**: candidates waiting in "Created" for more than {$this->staleDays()} days (by name), and how many open candidates have no CV.
            4. **Suggested next actions**: 2 to 4 concrete, prioritized steps for HR.

            Keep it under 250 words, use only the numbers above, and don't speculate about individual candidates beyond what's listed.
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
                name: 'audience',
                description: 'Optional: who the report is for, e.g. "the hiring manager" or "leadership". Defaults to the HR team.',
                required: false,
            ),
        ];
    }

    /**
     * The stale threshold, for use in the prompt text.
     */
    protected function staleDays(): int
    {
        return self::STALE_AFTER_DAYS;
    }
}
