<?php

namespace Tests\Unit\Repositories;

use App\Models\AgentRun;
use App\Models\User;
use App\Repositories\Agent\AgentRunRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentRunRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AgentRunRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AgentRunRepository();
    }

    private function makeRun(array $attrs = []): AgentRun
    {
        $user = User::factory()->create();

        return AgentRun::create(array_merge([
            'id'                     => \Illuminate\Support\Str::uuid(),
            'user_id'                => $user->id,
            'goal'                   => 'Test goal',
            'status'                 => 'pending',
            'step_count'             => 0,
            'max_steps'              => 30,
            'estimated_cost_cents'   => 0,
            'max_cost_cents'         => 50,
            'has_ungrounded_sections'=> false,
        ], $attrs));
    }

    public function test_create_returns_agent_run_with_correct_attributes(): void
    {
        $user = User::factory()->create();

        $run = $this->repo->create($user->id, 'Research competitors');

        $this->assertDatabaseHas('agent_runs', [
            'user_id' => $user->id,
            'goal'    => 'Research competitors',
            'status'  => 'pending',
        ]);
        $this->assertInstanceOf(AgentRun::class, $run);
    }

    public function test_mark_running_sets_status(): void
    {
        $run = $this->makeRun();
        $this->repo->markRunning($run);

        $this->assertSame('running', $run->fresh()->status);
    }

    public function test_increment_step_increases_step_count(): void
    {
        $run = $this->makeRun(['step_count' => 3]);
        $this->repo->incrementStep($run);

        $this->assertSame(4, $run->fresh()->step_count);
    }

    public function test_add_cost_accumulates_cents(): void
    {
        $run = $this->makeRun(['estimated_cost_cents' => 10]);
        $this->repo->addCost($run, 15);

        $this->assertSame(25, $run->fresh()->estimated_cost_cents);
    }

    public function test_is_over_budget_returns_false_below_ceiling(): void
    {
        $run = $this->makeRun(['estimated_cost_cents' => 49, 'max_cost_cents' => 50]);

        $this->assertFalse($this->repo->isOverBudget($run));
    }

    public function test_is_over_budget_returns_true_at_boundary(): void
    {
        $run = $this->makeRun(['estimated_cost_cents' => 50, 'max_cost_cents' => 50]);

        $this->assertTrue($this->repo->isOverBudget($run));
    }

    public function test_is_over_budget_returns_true_above_ceiling(): void
    {
        $run = $this->makeRun(['estimated_cost_cents' => 60, 'max_cost_cents' => 50]);

        $this->assertTrue($this->repo->isOverBudget($run));
    }

    public function test_mark_pending_review_never_sets_status_done(): void
    {
        $run = $this->makeRun(['status' => 'running']);
        $this->repo->markPendingReview($run, 'Report content', false);

        $fresh = $run->fresh();
        $this->assertSame('pending_review', $fresh->status);
        $this->assertNotSame('done', $fresh->status);
        $this->assertSame('Report content', $fresh->final_report);
    }

    public function test_mark_aborted_sets_status_aborted(): void
    {
        $run = $this->makeRun(['status' => 'running']);
        $this->repo->markAborted($run);

        $this->assertSame('aborted', $run->fresh()->status);
    }

    public function test_mark_aborted_leaves_final_report_untouched_when_none_passed(): void
    {
        $run = $this->makeRun(['status' => 'running', 'final_report' => 'Existing report']);
        $this->repo->markAborted($run);

        $this->assertSame('Existing report', $run->fresh()->final_report);
    }

    public function test_mark_aborted_saves_final_report_when_passed(): void
    {
        $run = $this->makeRun(['status' => 'running']);
        $this->repo->markAborted($run, 'Partial report');

        $this->assertSame('Partial report', $run->fresh()->final_report);
    }

    public function test_mark_aborted_saves_empty_string_final_report(): void
    {
        $run = $this->makeRun(['status' => 'running']);
        $this->repo->markAborted($run, '');

        $this->assertSame('', $run->fresh()->final_report);
    }

    public function test_set_pdf_path_persists_path(): void
    {
        $run = $this->makeRun();
        $this->repo->setPdfPath($run, '/reports/abc.pdf');

        $this->assertSame('/reports/abc.pdf', $run->fresh()->pdf_path);
    }

    public function test_get_pending_review_returns_only_pending_runs(): void
    {
        $this->makeRun(['status' => 'pending_review']);
        $this->makeRun(['status' => 'pending_review']);
        $this->makeRun(['status' => 'done']);
        $this->makeRun(['status' => 'running']);

        $results = $this->repo->getPendingReview();

        $this->assertCount(2, $results);
        $results->each(fn($r) => $this->assertSame('pending_review', $r->status));
    }

    public function test_find_and_lock_returns_the_run(): void
    {
        $run = $this->makeRun();

        $found = $this->repo->findAndLock($run->id);

        $this->assertSame($run->id, $found->id);
    }
}
