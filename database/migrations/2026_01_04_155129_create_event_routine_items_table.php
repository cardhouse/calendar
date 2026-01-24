<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_routine_items', function (Blueprint $table) {
            $table->id();
            $table->morphs('eventable'); // eventable_type + eventable_id for CalendarEvent or DepartureTime
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            // Unique constraint: one item name per child per event
            $table->unique(['eventable_type', 'eventable_id', 'child_id', 'name'], 'event_routine_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_routine_items');
    }
};
