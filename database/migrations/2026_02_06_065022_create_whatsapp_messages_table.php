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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->text('message');
            $table->string('intent', 50)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->string('fonnte_message_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
