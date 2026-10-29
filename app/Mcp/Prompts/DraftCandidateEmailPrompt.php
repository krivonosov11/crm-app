<?php

namespace App\Mcp\Prompts;

use App\Mcp\Concerns\InteractsWithCandidates;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('draft-candidate-email')]
#[Title('Draft Candidate Email')]
#[Description('Draft an email to a candidate: interview invitation, status update, offer/approval or rejection.')]
class DraftCandidateEmailPrompt extends Prompt
{
    /**
     * Guidance for each kind of email.
     *
     * @var array<string, string>
     */
    protected const array PURPOSES = [
        'interview_invite' => 'Invite the candidate to an interview. Leave clear placeholders like [DATE], [TIME] and [LOCATION / VIDEO LINK] for details HR will fill in.',
        'status_update' => 'Give the candidate a friendly update on where their application stands, based on their current status, and set expectations for next steps.',
        'approval' => 'Tell the candidate they have been approved for the next stage or an offer. Be warm and clear, and leave placeholders for anything specific such as [START DATE] or [NEXT STEPS].',
        'rejection' => 'Let the candidate know they were not selected. Be kind, respectful and brief, thank them for their time, and do not give reasons that could be read as discriminatory.',
    ];

    use InteractsWithCandidates;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        $staff = $this->actingStaff($request);

        if ($staff === null) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'candidate_id' => ['required', 'integer', 'min:1'],
            'purpose' => ['required', 'string', Rule::in(array_keys(self::PURPOSES))],
            'tone' => ['nullable', 'string', 'max:100'],
        ], [
            'purpose.in' => 'Purpose must be one of: '.implode(', ', array_keys(self::PURPOSES)).'.',
        ]);

        $candidate = $this->findCandidate((int) $validated['candidate_id']);

        if ($candidate === null) {
            return Response::error("No candidate found with ID {$validated['candidate_id']}.");
        }

        $sender = $staff->name;
        $tone = $validated['tone'] ?? 'professional and friendly';
        $guidance = self::PURPOSES[$validated['purpose']];
        $data = $this->toPromptJson([
            'name' => $candidate->name,
            'email' => $candidate->email,
            'status' => $candidate->status?->label(),
            'summary' => $candidate->candidateProfile?->summary,
        ]);

        return Response::text(<<<TEXT
            Draft an email from our HR team to a job candidate.

            Goal: {$guidance}
            Tone: {$tone}.
            Sign it as: {$sender}, HR team.

            Candidate (from the CRM):
            ```json
            {$data}
            ```

            Write:
            - A subject line.
            - The email body, addressed to the candidate by first name. Keep it under 200 words.
            - You may reference something specific from their summary to make it personal, but don't make up details.

            This is a draft for the HR specialist to review. Don't claim it has been sent, and don't change the candidate's status.
            If the email implies a status change (for example, a rejection), remind the HR specialist that they can update the status with the update-candidate-status tool once the email goes out.
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
                name: 'purpose',
                description: 'The kind of email: interview_invite, status_update, approval or rejection.',
                required: true,
            ),
            new Argument(
                name: 'tone',
                description: 'Optional: the tone, e.g. "formal" or "warm and casual". Defaults to professional and friendly.',
                required: false,
            ),
        ];
    }
}
