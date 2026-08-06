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
        Schema::create('bell_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('time');
            $table->json('days'); // Store array of active days, e.g. ["Monday", "Tuesday"]
            $table->string('sound_type')->default('chime'); // chime, custom, tts
            $table->string('sound_file')->nullable(); // path to uploaded file
            $table->text('tts_text')->nullable(); // Indonesian text to be spoken
            $table->integer('volume')->default(100); // 0 to 100
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bell_schedules');
    }
};
