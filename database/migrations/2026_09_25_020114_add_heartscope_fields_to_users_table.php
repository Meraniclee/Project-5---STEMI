<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'admin',
                'consultant',
                'doctor',
            ])->default('consultant')->after('password');

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active')->after('role');

            $table->string('phone', 30)->nullable()->after('status');
            $table->string('specialization')->nullable()->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('specialization');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'status',
                'phone',
                'specialization',
                'last_login_at',
            ]);
        });
    }
};