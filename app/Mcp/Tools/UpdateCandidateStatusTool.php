<?php

namespace App\Mcp\Tools;

use App\Enums\CandidateStatus;
use App\Mcp\Concerns\InteractsWithCandidates;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
#[Name('update-candidate-status')]
#[Title('Update Candidate Status')]
#[Description('Changes a candidate\'s application status (created, processing, approved or rejected). Confirm with the user before approving or rejecting a candidate.')]
class UpdateCandidateStatusTool extends Tool
{
    use InteractsWithCandidates;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $this->actingStaff($request);

        if ($user === null) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'candidate_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(CandidateStatus::names())],
        ], [
            'status.in' => 'Status must be one of: '.implode(', ', CandidateStatus::names()).'.',
        ]);

        $candidate = $this->findCandidate((int) $validated['candidate_id']);

        if ($candidate === null) {
            return Response::error("No candidate found with ID {$validated['candidate_id']}.");
        }

        if (Gate::forUser($user)->denies('update', $candidate)) {
            return $this->unauthorizedResponse();
        }

        $previous = $candidate->status;
        $candidate->update(['status' => CandidateStatus::tryFromName($validated['status'])]);

        return Response::json([
            'message' => "Status of {$candidate->name} changed from {$previous?->label()} to {$candidate->status?->label()}.",
            'previous_status' => $this->statusPayload($previous),
            'candidate' => $this->candidatePayload($candidate),
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
            'candidate_id' => $schema->integer()
                ->min(1)
                ->description('The candidate ID, as returned by the list-candidates tool.')
                ->required(),

            'status' => $schema->string()
                ->enum(CandidateStatus::names())
                ->description('The new application status.')
                ->required(),
        ];
    }
}
