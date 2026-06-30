<?php

namespace App\Repositories\Agent;

use App\Models\AgentStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AgentStepRepository
{
    public function getForRun(string $runId): Collection
    {
        return AgentStep::where('agent_run_id', $runId)
            ->orderBy('step_number')
            ->get();
    }

    public function record(string $runId, string $type, string $content, ?string $toolName = null, ?array $toolInput = null): void
    {
        $stepNumber = AgentStep::where('agent_run_id', $runId)->count() + 1;

        AgentStep::create([
            'id'           => Str::uuid(),
            'agent_run_id' => $runId,
            'step_number'  => $stepNumber,
            'type'         => $type,
            'tool_name'    => $toolName,
            'tool_input'   => $toolInput,
            'content'      => $content,
        ]);
    }
}
