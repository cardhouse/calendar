<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RoutineItemTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutineItemTemplate extends Model
{
    /** @use HasFactory<RoutineItemTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }
}
