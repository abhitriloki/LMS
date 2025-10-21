<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('path_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')->constrained('ai_learning_paths')->cascadeOnDelete();
            $table->text('reason');
            $table->json('previous_sequence');
            $table->json('new_sequence');
            $table->json('performance_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('path_adjustments');
    }
};
