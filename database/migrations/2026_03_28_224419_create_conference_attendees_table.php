<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conference_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->enum('status', [
                'registered',
                'attended',
                'cancelled',
            ])->default('registered');
            $table->string('certificate_path')->nullable();
            $table->timestamps();

            $table->unique(['conference_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conference_attendees');
    }
};