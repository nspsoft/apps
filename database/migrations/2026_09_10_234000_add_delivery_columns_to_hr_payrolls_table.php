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
        Schema::table('hr_payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_payrolls', 'wa_sent_at')) {
                $table->timestamp('wa_sent_at')->nullable()->after('note');
            }
            if (!Schema::hasColumn('hr_payrolls', 'wa_status')) {
                $table->string('wa_status', 20)->nullable()->after('wa_sent_at'); // 'sent', 'failed', 'pending'
            }
            if (!Schema::hasColumn('hr_payrolls', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('wa_status');
            }
            if (!Schema::hasColumn('hr_payrolls', 'email_status')) {
                $table->string('email_status', 20)->nullable()->after('email_sent_at'); // 'sent', 'failed', 'pending'
            }
            if (!Schema::hasColumn('hr_payrolls', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('email_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'wa_sent_at',
                'wa_status',
                'email_sent_at',
                'email_status',
                'pdf_path',
            ]);
        });
    }
};
