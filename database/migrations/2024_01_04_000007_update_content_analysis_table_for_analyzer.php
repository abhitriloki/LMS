<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_analysis', function (Blueprint $table) {
            // Add new columns
            $table->decimal('overall_score', 5, 2)->nullable()->after('engagement_score');
            $table->string('complexity_level')->nullable()->after('overall_score');
            $table->boolean('is_current')->default(true)->after('accessibility_issues');
            
            // Rename column
            $table->renameColumn('identified_gaps', 'content_gaps');
        });
    }

    public function down(): void
    {
        Schema::table('content_analysis', function (Blueprint $table) {
            $table->dropColumn(['overall_score', 'complexity_level', 'is_current']);
            $table->renameColumn('content_gaps', 'identified_gaps');
        });
    }
};
