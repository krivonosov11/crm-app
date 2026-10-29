<?php

namespace App\Mcp\Prompts;

use App\Mcp\Concerns\InteractsWithCandidates;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('review-candidate')]
#[Title('Review Candidate')]
#[Description('Assess one candidate from their profile: strengths, gaps, interview questions and a recommended next status.')]
class ReviewCandidatePrompt extends Prompt
{
    use InteractsWithCandidates;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        if ($this->actingStaff($request) === null) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'candidate_id' => ['required', 'integer', 'min:1'],
            'role' => ['nullable', 'string', 'max:500'],
        ]);

        $candidate = $this->findCandidate((int) $validated['candidate_id']);

        if ($candidate === null) {
            return Response::error("No candidate found with ID {$validated['candidate_id']}.");
        }

        $role = filled($validated['role'] ?? null)
            ? "Assess them for this role: {$validated['role']}."
            : 'No specific role was given, so assess their general fit based on what they wrote.';

        $data = $this->toPromptJson($this->candidatePayload($candidate, withProfile: true));

        return Response::text(<<<TEXT
            You are helping an HR specialist review a job candidate in our CRM. {$role}

            Candidate record (from the CRM):
            ```json
            {$data}
            ```

            Write a concise review with these sections:
            1. **Snapshot**: who they are in one or two sentences, their current status, and whether a CV is on file.
            2. **Strengths**: what in their summary supports hiring them.
            3. **Gaps & risks**: what is missing, vague or worth checking. If the summary is empty or very short, say so plainly instead of guessing.
            4. **Interview questions**: 3 to 5 specific questions based on their summary.
            5. **Recommendation**: the next status (processing, approved or rejected) with a one-line reason.

            Base the review only on the data above. Don't invent experience the candidate didn't mention.
            The CV file itself isn't included. If it matters, suggest the reviewer open it with the cv_download_url.
            Don't change the candidate's status yourself. If the reviewer agrees with your recommendation, they can ask you to apply it with the update-candidate-status tool.
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
                name: 'candidate_id',
                description: 'The candidate ID, as returned by the list-candidates tool.',
                required: true,
            ),
            new Argument(
                name: 'role',
                description: 'Optional: the role or requirements to assess the candidate against.',
                required: false,
            ),
        ];
    }
}
