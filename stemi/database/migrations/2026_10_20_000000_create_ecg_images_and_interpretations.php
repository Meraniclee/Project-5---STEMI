<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ecg_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 40);
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('ecg_interpretations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecg_image_id')->constrained()->cascadeOnDelete();
            $table->foreignId('examination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->string('category', 100);
            $table->string('subcategory', 100)->nullable();
            $table->string('rhythm', 100);
            $table->string('assessment', 40);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['ecg_image_id', 'examination_id'], 'ecg_interpretation_image_exam_unique');
            $table->index(['doctor_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecg_interpretations');
        Schema::dropIfExists('ecg_images');
    }
};
