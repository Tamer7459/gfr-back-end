<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('reviewer_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->text('feedback')->nullable();
            $table->enum('decision', [
                'pending',
                'accept',
                'reject',
                'revision',
            ])->default('pending');
            $table->timestamps();

            // مراجع واحد لكل ورقة
            $table->unique(['journal_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};