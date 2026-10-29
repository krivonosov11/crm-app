<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithCandidates;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Name('get-candidate')]
#[Title('Get Candidate')]
#[Description('Gets one candidate by ID, including their application status, self-written summary, and CV details (file name and a download link for the web app).')]
class GetCandidateTool extends Tool
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

        $validated = $request->validate([
            'candidate_id' => ['required', 'integer', 'min:1'],
        ]);

        $candidate = $this->findCandidate((int) $validated['candidate_id']);

        if ($candidate === null) {
            return Response::error("No candidate found with ID {$validated['candidate_id']}. Use the list-candidates tool to find candidate IDs.");
        }

        return Response::json($this->candidatePayload($candidate, withProfile: true));
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
        ];
    }
}
