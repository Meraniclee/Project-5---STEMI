<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ecg_interpretations', function (Blueprint $table) {
            $table->json('lead_findings')->nullable();
            $table->json('overall')->nullable();
            $table->json('stemi')->nullable();
            $table->json('extraction')->nullable();
            $table->boolean('annotation_complete')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('ecg_interpretations', fn (Blueprint $table) => $table->dropColumn(['lead_findings', 'overall', 'stemi', 'extraction', 'annotation_complete']));
    }
};
