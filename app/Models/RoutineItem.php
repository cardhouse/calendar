<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RoutineItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class RoutineItem extends Model
{
    /** @use HasFactory<RoutineItemFactory> */
    use HasFactory;

    protected $fillable = [
        'child_id',
        'name',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Child, $this>
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * @return HasMany<RoutineCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(RoutineCompletion::class);
    }

    /**
     * Check if this item is completed for a given date.
     * Uses eager-loaded completions if available to avoid N+1 queries.
     */
    public function isCompletedFor(?Carbon $date = null): bool
    {
        $date ??= today();

        // Use eager-loaded completions if available
        if ($this->relationLoaded('completions')) {
            return $this->completions
                ->contains(fn ($c) => $c->completion_date->isSameDay($date));
        }

        return $this->completions()
            ->whereDate('completion_date', $date)
            ->exists();
    }

    /**
     * Mark this item as completed for today.
     */
    public function markComplete(): void
    {
        if (! $this->isCompletedFor()) {
            $this->completions()->create([
                'completion_date' => today(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Mark this item as incomplete for today.
     */
    public function markIncomplete(): void
    {
        $this->completions()
            ->whereDate('completion_date', today())
            ->delete();
    }

    /**
     * Toggle completion status for today.
     */
    public function toggleCompletion(): bool
    {
        if ($this->isCompletedFor()) {
            $this->markIncomplete();

            return false;
        }

        $this->markComplete();

        return true;
    }
}
