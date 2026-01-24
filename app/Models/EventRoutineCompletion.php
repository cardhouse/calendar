<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRoutineCompletion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_routine_item_id',
        'completion_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EventRoutineItem, $this>
     */
    public function eventRoutineItem(): BelongsTo
    {
        return $this->belongsTo(EventRoutineItem::class);
    }
}
