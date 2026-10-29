<?php

use App\Enums\CandidateStatus;
use App\Mcp\Prompts\DraftCandidateEmailPrompt;
use App\Mcp\Prompts\PipelineReportPrompt;
use App\Mcp\Prompts\ReviewCandidatePrompt;
use App\Mcp\Prompts\ShortlistCandidatesPrompt;
use App\Mcp\Servers\CandidatesServer;
use App\Models\CandidateProfile;
use App\Models\User;

beforeEach(function () {
    $this->hr = User::factory()->hr()->create(['name' => 'Helen HR']);
});

test('prompts expose stable names', function (string $prompt, string $name) {
    CandidatesServer::actingAs($this->hr)
        ->prompt($prompt, ['candidate_id' => 1, 'purpose' => 'rejection', 'requirements' => 'x'])
        ->assertName($name);
})->with([
    [ReviewCandidatePrompt::class, 'review-candidate'],
    [ShortlistCandidatesPrompt::class, 'shortlist-candidates'],
    [DraftCandidateEmailPrompt::class, 'draft-candidate-email'],
    [PipelineReportPrompt::class, 'pipeline-report'],
]);

test('review prompt embeds the candidate profile and role', function () {
    $profile = CandidateProfile::factory()->withCv()->create(['summary' => 'Built payment systems in Laravel.']);

    CandidatesServer::actingAs($this->hr)
        ->prompt(ReviewCandidatePrompt::class, [
            'candidate_id' => $profile->user_id,
            'role' => 'Senior backend engineer',
        ])
        ->assertOk()
        ->assertSee([
            'Built payment systems in Laravel.',
            'Senior backend engineer',
            $profile->user->name,
            'cv.pdf',
            'Recommendation',
        ]);
});

test('review prompt rejects missing or non candidate ids', function () {
    $admin = User::factory()->admin()->create();

    CandidatesServer::actingAs($this->hr)
        ->prompt(ReviewCandidatePrompt::class, ['candidate_id' => $admin->id])
        ->assertHasErrors();

    CandidatesServer::actingAs($this->hr)
        ->prompt(ReviewCandidatePrompt::class, [])
        ->assertHasErrors();
});

test('shortlist prompt includes open candidates by default', function () {
    CandidateProfile::factory()->create([
        'user_id' => User::factory()->withStatus(CandidateStatus::Processing)->create(['name' => 'Open Olivia']),
        'summary' => 'React and Laravel',
    ]);
    User::factory()->withStatus(CandidateStatus::Rejected)->create(['name' => 'Rejected Ray']);

    CandidatesServer::actingAs($this->hr)
        ->prompt(ShortlistCandidatesPrompt::class, ['requirements' => 'Full-stack Laravel + React'])
        ->assertOk()
        ->assertSee(['Full-stack Laravel + React', 'Open Olivia', 'React and Laravel'])
        ->assertDontSee('Rejected Ray');
});

test('shortlist prompt can target a status and handles an empty pool', function () {
    User::factory()->withStatus(CandidateStatus::Rejected)->create(['name' => 'Rejected Ray']);

    CandidatesServer::actingAs($this->hr)
        ->prompt(ShortlistCandidatesPrompt::class, ['requirements' => 'Anyone', 'status' => 'rejected'])
        ->assertOk()
        ->assertSee('Rejected Ray');

    CandidatesServer::actingAs($this->hr)
        ->prompt(ShortlistCandidatesPrompt::class, ['requirements' => 'Anyone', 'status' => 'approved'])
        ->assertOk()
        ->assertSee('nobody to shortlist');
});

test('email prompt uses the purpose guidance and signs as the acting user', function () {
    $candidate = User::factory()->candidate()->create(['name' => 'Jane Doe']);

    CandidatesServer::actingAs($this->hr)
        ->prompt(DraftCandidateEmailPrompt::class, ['candidate_id' => $candidate->id, 'purpose' => 'interview_invite'])
        ->assertOk()
        ->assertSee(['Jane Doe', '[DATE]', 'Helen HR, HR team', 'professional and friendly']);
});

test('email prompt validates the purpose', function () {
    $candidate = User::factory()->candidate()->create();

    CandidatesServer::actingAs($this->hr)
        ->prompt(DraftCandidateEmailPrompt::class, ['candidate_id' => $candidate->id, 'purpose' => 'marketing'])
        ->assertHasErrors(['Purpose must be one of: interview_invite, status_update, approval, rejection.']);
});

test('pipeline report includes stats and candidates waiting too long', function () {
    User::factory()->candidate()->create(['name' => 'Waiting Walter', 'created_at' => now()->subDays(10)]);
    User::factory()->candidate()->create(['name' => 'Fresh Fiona']);
    User::factory()->withStatus(CandidateStatus::Approved)->create();

    CandidatesServer::actingAs($this->hr)
        ->prompt(PipelineReportPrompt::class, ['audience' => 'leadership'])
        ->assertOk()
        ->assertSee(['leadership', '"total": 3', 'Waiting Walter', '"open_candidates_without_cv": 2'])
        ->assertDontSee('Fresh Fiona');
});

test('candidates and unauthenticated clients cannot use prompts', function () {
    $candidate = User::factory()->candidate()->create();

    CandidatesServer::actingAs($candidate)
        ->prompt(PipelineReportPrompt::class)
        ->assertHasErrors(['Access denied.']);

    config(['services.mcp.acting_user_email' => null]);

    CandidatesServer::prompt(ReviewCandidatePrompt::class, ['candidate_id' => $candidate->id])
        ->assertHasErrors(['Access denied.']);
});
