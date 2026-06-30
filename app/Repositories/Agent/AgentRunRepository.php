<?php

namespace App\Repositories\Agent;

use App\Models\AgentRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AgentRunRepository
{
    public function create(int $userId, string $goal): AgentRun
    {
        return AgentRun::create([
            'id'      => Str::uuid(),
            'user_id' => $userId,
            'goal'    => $goal,
            'status'  => 'pending',
        ]);
    }

    public function markRunning(AgentRun $run): void
    {
        $run->update(['status' => 'running']);
    }

    public function incrementStep(AgentRun $run): void
    {
        $run->increment('step_count');
    }

    public function addCost(AgentRun $run, int $cents): void
    {
        $run->increment('estimated_cost_cents', $cents);
    }

    public function isOverBudget(AgentRun $run): bool
    {
        return $run->estimated_cost_cents >= $run->max_cost_cents;
    }

    public function markPendingReview(AgentRun $run, string $finalReport, bool $hasUngroundedSections): void
    {
        $run->update([
            'status'                  => 'pending_review',
            'final_report'            => $finalReport,
            'has_ungrounded_sections' => $hasUngroundedSections,
        ]);
    }

    public function approve(AgentRun $run, int $reviewerId, ?string $notes = null): void
    {
        $run->update([
            'status'       => 'done',
            'reviewed_by'  => $reviewerId,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
    }

    public function reject(AgentRun $run, int $reviewerId, string $notes): void
    {
        $run->update([
            'status'       => 'rejected',
            'reviewed_by'  => $reviewerId,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
    }

    public function markAborted(AgentRun $run, ?string $finalReport = null): void
    {
        $run->update(array_filter([
            'status'       => 'aborted',
            'final_report' => $finalReport,
        ], fn($v) => $v !== null));
    }

    public function setPdfPath(AgentRun $run, string $path): void
    {
        $run->update(['pdf_path' => $path]);
    }

    public function getPendingReview(): Collection
    {
        return AgentRun::where('status', 'pending_review')
            ->orderBy('created_at')
            ->get(['id', 'goal', 'final_report', 'has_ungrounded_sections', 'created_at']);
    }

    /** Fetches a run inside the current transaction with a pessimistic lock, preventing concurrent approvals. */
    public function findAndLock(string $id): AgentRun
    {
        return AgentRun::lockForUpdate()->findOrFail($id);
    }
}
