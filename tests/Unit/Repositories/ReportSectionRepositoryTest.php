<?php

namespace Tests\Unit\Repositories;

use App\Models\AgentRun;
use App\Models\ReportSection;
use App\Models\User;
use App\Repositories\Agent\ReportSectionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportSectionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ReportSectionRepository $repo;
    private AgentRun $run;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ReportSectionRepository();

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

    private function makeSection(array $attrs = []): ReportSection
    {
        return ReportSection::create(array_merge([
            'id'              => Str::uuid(),
            'agent_run_id'    => $this->run->id,
            'competitor_name' => 'Acme',
            'section'         => 'pricing',
            'content'         => 'Some content',
            'is_grounded'     => true,
        ], $attrs));
    }

    public function test_get_for_run_returns_sections_ordered_by_competitor_name(): void
    {
        $this->makeSection(['competitor_name' => 'Zebra', 'section' => 'pricing']);
        $this->makeSection(['competitor_name' => 'Acme', 'section' => 'pricing']);

        $sections = $this->repo->getForRun($this->run->id);

        $this->assertSame('Acme', $sections[0]->competitor_name);
        $this->assertSame('Zebra', $sections[1]->competitor_name);
    }

    public function test_mark_grounded_updates_is_grounded(): void
    {
        $section = $this->makeSection(['is_grounded' => true]);
        $this->repo->markGrounded($section, false);

        $this->assertFalse($section->fresh()->is_grounded);
    }

    public function test_has_ungrounded_sections_returns_true_when_ungrounded_exists(): void
    {
        $this->makeSection(['is_grounded' => true]);
        $this->makeSection(['section' => 'features', 'is_grounded' => false]);

        $this->assertTrue($this->repo->hasUngroundedSections($this->run->id));
    }

    public function test_has_ungrounded_sections_returns_false_when_all_grounded(): void
    {
        $this->makeSection(['is_grounded' => true]);
        $this->makeSection(['section' => 'features', 'is_grounded' => true]);

        $this->assertFalse($this->repo->hasUngroundedSections($this->run->id));
    }

    public function test_compile_markdown_includes_warning_for_ungrounded_sections(): void
    {
        $this->makeSection(['competitor_name' => 'Acme', 'section' => 'pricing', 'content' => 'Costs $10/mo', 'is_grounded' => false]);

        $markdown = $this->repo->compileMarkdown($this->run->id);

        $this->assertStringContainsString('⚠️ Unverified', $markdown);
        $this->assertStringContainsString('Costs $10/mo', $markdown);
    }

    public function test_compile_markdown_has_no_warning_for_grounded_sections(): void
    {
        $this->makeSection(['is_grounded' => true, 'content' => 'Verified content']);

        $markdown = $this->repo->compileMarkdown($this->run->id);

        $this->assertStringNotContainsString('⚠️ Unverified', $markdown);
        $this->assertStringContainsString('Verified content', $markdown);
    }
}
