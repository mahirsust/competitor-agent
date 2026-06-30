<?php

namespace Tests\Unit\Repositories;

use App\Models\AgentRun;
use App\Models\AgentStep;
use App\Models\User;
use App\Repositories\Agent\AgentStepRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentStepRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AgentStepRepository $repo;
    private AgentRun $run;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AgentStepRepository();

        $user = User::factory()->create();
        $this->run = AgentRun::create([
            'id'                      => Str::uuid(),
            'user_id'                 => $user->id,
            'goal'                    => 'Test goal',
            'status'                  => 'running',
            'step_count'              => 0,
            'max_steps'               => 30,
            'estimated_cost_cents'    => 0,
            'max_cost_cents'          => 50,
            'has_ungrounded_sections' => false,
        ]);
    }

    public function test_record_creates_step_with_incrementing_step_number(): void
    {
        $this->repo->record($this->run->id, 'thought', 'First thought');
        $this->repo->record($this->run->id, 'observation', 'First observation');

        $steps = AgentStep::where('agent_run_id', $this->run->id)->orderBy('step_number')->get();

        $this->assertCount(2, $steps);
        $this->assertSame(1, $steps[0]->step_number);
        $this->assertSame(2, $steps[1]->step_number);
    }

    public function test_record_stores_tool_name_and_input(): void
    {
        $this->repo->record($this->run->id, 'tool_call', 'Calling web_search', 'web_search', ['query' => 'test']);

        $step = AgentStep::where('agent_run_id', $this->run->id)->first();

        $this->assertSame('web_search', $step->tool_name);
        $this->assertSame(['query' => 'test'], $step->tool_input);
    }

    public function test_get_for_run_returns_steps_ordered_by_step_number(): void
    {
        $this->repo->record($this->run->id, 'thought', 'A');
        $this->repo->record($this->run->id, 'observation', 'B');
        $this->repo->record($this->run->id, 'finish', 'C');

        $steps = $this->repo->getForRun($this->run->id);

        $this->assertCount(3, $steps);
        $this->assertSame('thought', $steps[0]->type);
        $this->assertSame('observation', $steps[1]->type);
        $this->assertSame('finish', $steps[2]->type);
    }

    public function test_get_for_run_returns_empty_collection_for_unknown_run(): void
    {
        $steps = $this->repo->getForRun(Str::uuid());

        $this->assertCount(0, $steps);
    }
}
