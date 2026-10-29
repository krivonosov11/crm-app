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
#[Name('candidate-pipeline-stats')]
#[Title('Candidate Pipeline Stats')]
#[Description('Summarizes the hiring pipeline: total candidates, how many are in each application status, and how many have uploaded a CV.')]
class CandidatePipelineStatsTool extends Tool
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

        return Response::json($this->pipelineStats());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
