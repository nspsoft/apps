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
            if (!Schema::hasColumn('users', 'mfa_method')) {
                $table->string('mfa_method', 20)->default('whatsapp')->after('mfa_enabled')->index();
            }
            if (!Schema::hasColumn('users', 'mfa_totp_secret')) {
                $table->text('mfa_totp_secret')->nullable()->after('mfa_phone');
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
                'mfa_method',
                'mfa_totp_secret',
            ]);
        });
    }
};
