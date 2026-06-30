<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentRun extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'goal', 'status', 'step_count', 'max_steps',
        'estimated_cost_cents', 'max_cost_cents', 'final_report', 'pdf_path',
        'has_ungrounded_sections', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'has_ungrounded_sections' => 'boolean',
        'reviewed_at'             => 'datetime',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(AgentStep::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ReportSection::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
