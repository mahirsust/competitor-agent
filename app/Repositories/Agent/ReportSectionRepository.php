<?php

namespace App\Repositories\Agent;

use App\Models\ReportSection;
use Illuminate\Support\Collection;

class ReportSectionRepository
{
    public function getForRun(string $runId): Collection
    {
        return ReportSection::where('agent_run_id', $runId)
            ->orderBy('competitor_name')
            ->get();
    }

    public function markGrounded(ReportSection $section, bool $isGrounded): void
    {
        $section->update(['is_grounded' => $isGrounded]);
    }

    public function hasUngroundedSections(string $runId): bool
    {
        return ReportSection::where('agent_run_id', $runId)
            ->where('is_grounded', false)
            ->exists();
    }

    public function compileMarkdown(string $runId): string
    {
        return $this->getForRun($runId)
            ->map(function ($s) {
                $heading = "## {$s->competitor_name} — {$s->section}";
                $warning = $s->is_grounded ? '' : "\n\n> ⚠️ Unverified — could not confirm this against scraped sources.";
                return "{$heading}{$warning}\n\n{$s->content}";
            })
            ->join("\n\n---\n\n");
    }
}
