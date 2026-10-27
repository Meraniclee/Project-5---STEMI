<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('subject_id',100)->unique();
            $table->enum('age_category',['Neonate','Infant','Child','Adolescent','Young Adult','Adult','Older Adult'])->nullable();
            $table->enum('sex',['Male','Female'])->nullable();
            $table->enum('case_status',['pending','in_progress','completed','needs_review'])->default('pending');
            $table->timestamps();
            $table->index('case_status');
        });
    }
    public function down(): void { Schema::dropIfExists('patients'); }
};
