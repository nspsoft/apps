<?php

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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'mfa_enabled')) {
                $table->boolean('mfa_enabled')->default(false)->index();
            }
            if (!Schema::hasColumn('users', 'mfa_phone')) {
                $table->string('mfa_phone', 30)->nullable();
            }
            if (!Schema::hasColumn('users', 'mfa_otp')) {
                $table->string('mfa_otp', 100)->nullable();
            }
            if (!Schema::hasColumn('users', 'mfa_otp_expires_at')) {
                $table->timestamp('mfa_otp_expires_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'mfa_recovery_codes')) {
                $table->text('mfa_recovery_codes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mfa_enabled',
                'mfa_phone',
                'mfa_otp',
                'mfa_otp_expires_at',
                'mfa_recovery_codes',
            ]);
        });
    }
};
