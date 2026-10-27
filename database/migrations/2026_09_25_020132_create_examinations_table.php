<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examinations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('doctor_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('status', [
                'draft',
                'in_progress',
                'completed',
            ])->default('draft');

            $table->enum('overall_outcome', [
                'Normal',
                'Abnormal',
                'Unable to Determine',
            ])->nullable();

            $table->text('doctor_notes')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index([
                'patient_id',
                'doctor_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examinations');
    }
};