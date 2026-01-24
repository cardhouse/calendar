<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departure_times', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('departure_time');
            $table->json('applicable_days')->default('["monday","tuesday","wednesday","thursday","friday"]');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departure_times');
    }
};
