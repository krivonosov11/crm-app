<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\DraftCandidateEmailPrompt;
use App\Mcp\Prompts\PipelineReportPrompt;
use App\Mcp\Prompts\ReviewCandidatePrompt;
use App\Mcp\Prompts\ShortlistCandidatesPrompt;
use App\Mcp\Tools\CandidatePipelineStatsTool;
use App\Mcp\Tools\GetCandidateTool;
use App\Mcp\Tools\ListCandidatesTool;
use App\Mcp\Tools\UpdateCandidateStatusTool;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('CRM Candidates')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
    Tools for HR staff to review job candidates in the CRM.
    Candidates move through the statuses: created -> processing -> approved or rejected.
    Use list-candidates to find candidate IDs (search by name/email, filter by status), get-candidate for a candidate's summary and CV details,
    and candidate-pipeline-stats for an overview. Only change a status with update-candidate-status when the user asked for it,
    and confirm before approving or rejecting someone.
    Prompts: review-candidate, shortlist-candidates, draft-candidate-email and pipeline-report load live CRM data for common HR tasks.
    TEXT)]
class CandidatesServer extends Server
{
    protected array $tools = [
        ListCandidatesTool::class,
        GetCandidateTool::class,
        CandidatePipelineStatsTool::class,
        UpdateCandidateStatusTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        ReviewCandidatePrompt::class,
        ShortlistCandidatesPrompt::class,
        DraftCandidateEmailPrompt::class,
        PipelineReportPrompt::class,
    ];

    /**
     * Act as the configured HR / admin account when no user is authenticated.
     *
     * Local (stdio) servers have no HTTP authentication, so the acting user comes
     * from the MCP_USER_EMAIL environment variable. Every tool still checks the
     * application's policies against this user.
     */
    protected function boot(): void
    {
        if (Auth::check()) {
            return;
        }

        $email = config('services.mcp.acting_user_email');

        if (! is_string($email) || $email === '') {
            return;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            Auth::setUser($user);
        }
    }
}
