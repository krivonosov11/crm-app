<?php

use App\Enums\CandidateStatus;
use App\Mcp\Servers\CandidatesServer;
use App\Mcp\Tools\CandidatePipelineStatsTool;
use App\Mcp\Tools\GetCandidateTool;
use App\Mcp\Tools\ListCandidatesTool;
use App\Mcp\Tools\UpdateCandidateStatusTool;
use App\Models\CandidateProfile;
use App\Models\User;

beforeEach(function () {
    $this->hr = User::factory()->hr()->create();
});

test('tools expose the names referenced in the server instructions', function (string $tool, string $name) {
    CandidatesServer::actingAs($this->hr)
        ->tool($tool, ['candidate_id' => 1, 'status' => 'created'])
        ->assertName($name);
})->with([
    [ListCandidatesTool::class, 'list-candidates'],
    [GetCandidateTool::class, 'get-candidate'],
    [CandidatePipelineStatsTool::class, 'candidate-pipeline-stats'],
    [UpdateCandidateStatusTool::class, 'update-candidate-status'],
]);

test('hr can list candidates without seeing staff accounts', function () {
    $candidate = User::factory()->candidate()->create(['name' => 'Jane Candidate']);
    User::factory()->admin()->create(['name' => 'Some Admin']);

    CandidatesServer::actingAs($this->hr)
        ->tool(ListCandidatesTool::class)
        ->assertOk()
        ->assertSee('Jane Candidate')
        ->assertSee('"total":1')
        ->assertDontSee('Some Admin')
        ->assertDontSee($this->hr->email);

    expect($candidate->status)->toBe(CandidateStatus::Created);
});

test('candidates can be searched and filtered by status', function () {
    User::factory()->withStatus(CandidateStatus::Approved)->create(['name' => 'Approved Alice']);
    User::factory()->withStatus(CandidateStatus::Created)->create(['name' => 'New Bob']);

    CandidatesServer::actingAs($this->hr)
        ->tool(ListCandidatesTool::class, ['status' => 'approved'])
        ->assertOk()
        ->assertSee('Approved Alice')
        ->assertDontSee('New Bob');

    CandidatesServer::actingAs($this->hr)
        ->tool(ListCandidatesTool::class, ['status' => 'created'])
        ->assertOk()
        ->assertSee('New Bob')
        ->assertDontSee('Approved Alice');

    CandidatesServer::actingAs($this->hr)
        ->tool(ListCandidatesTool::class, ['search' => 'alice'])
        ->assertOk()
        ->assertSee('Approved Alice')
        ->assertDontSee('New Bob');
});

test('listing rejects an unknown status', function () {
    CandidatesServer::actingAs($this->hr)
        ->tool(ListCandidatesTool::class, ['status' => 'hired'])
        ->assertHasErrors(['Status must be one of: created, processing, approved, rejected.']);
});

test('hr can get a candidate with their summary and cv details', function () {
    $profile = CandidateProfile::factory()->withCv()->create(['summary' => 'Ten years of Laravel.']);

    CandidatesServer::actingAs($this->hr)
        ->tool(GetCandidateTool::class, ['candidate_id' => $profile->user_id])
        ->assertOk()
        ->assertSee('Ten years of Laravel.')
        ->assertSee('cv.pdf')
        ->assertSee('"has_cv":true')
        ->assertDontSee('cvs/');
});

test('getting a non candidate or missing id returns an error', function () {
    $admin = User::factory()->admin()->create();

    CandidatesServer::actingAs($this->hr)
        ->tool(GetCandidateTool::class, ['candidate_id' => $admin->id])
        ->assertHasErrors();

    CandidatesServer::actingAs($this->hr)
        ->tool(GetCandidateTool::class, ['candidate_id' => 999999])
        ->assertHasErrors();
});

test('pipeline stats count candidates per status', function () {
    User::factory(2)->candidate()->create();
    User::factory()->withStatus(CandidateStatus::Rejected)->create();
    CandidateProfile::factory()->withCv()->create();

    CandidatesServer::actingAs($this->hr)
        ->tool(CandidatePipelineStatsTool::class)
        ->assertOk()
        ->assertSee('"total":4')
        ->assertSee('"name":"created","label":"Created","count":3')
        ->assertSee('"name":"rejected","label":"Rejected","count":1')
        ->assertSee('"with_cv":1');
});

test('hr can update a candidate status', function () {
    $candidate = User::factory()->candidate()->create();

    CandidatesServer::actingAs($this->hr)
        ->tool(UpdateCandidateStatusTool::class, ['candidate_id' => $candidate->id, 'status' => 'approved'])
        ->assertOk()
        ->assertSee('changed from Created to Approved');

    expect($candidate->refresh()->status)->toBe(CandidateStatus::Approved);
});

test('status updates cannot target staff accounts', function () {
    $admin = User::factory()->admin()->create();

    CandidatesServer::actingAs($this->hr)
        ->tool(UpdateCandidateStatusTool::class, ['candidate_id' => $admin->id, 'status' => 'approved'])
        ->assertHasErrors();

    expect($admin->refresh()->status)->toBeNull();
});

test('candidates cannot use the server', function () {
    $candidate = User::factory()->candidate()->create();
    $other = User::factory()->candidate()->create();

    CandidatesServer::actingAs($candidate)
        ->tool(ListCandidatesTool::class)
        ->assertHasErrors();

    CandidatesServer::actingAs($candidate)
        ->tool(UpdateCandidateStatusTool::class, ['candidate_id' => $other->id, 'status' => 'approved'])
        ->assertHasErrors();

    expect($other->refresh()->status)->toBe(CandidateStatus::Created);
});

test('without an acting user every tool is denied', function () {
    config(['services.mcp.acting_user_email' => null]);

    CandidatesServer::tool(ListCandidatesTool::class)
        ->assertHasErrors(['Access denied.']);
});

test('the server acts as the account configured in MCP_USER_EMAIL', function () {
    User::factory()->candidate()->create(['name' => 'Visible Candidate']);
    config(['services.mcp.acting_user_email' => $this->hr->email]);

    CandidatesServer::tool(ListCandidatesTool::class)
        ->assertOk()
        ->assertSee('Visible Candidate');
});

test('configuring a candidate account as the acting user grants no access', function () {
    $candidate = User::factory()->candidate()->create();
    config(['services.mcp.acting_user_email' => $candidate->email]);

    CandidatesServer::tool(CandidatePipelineStatsTool::class)
        ->assertHasErrors(['Access denied.']);
});
