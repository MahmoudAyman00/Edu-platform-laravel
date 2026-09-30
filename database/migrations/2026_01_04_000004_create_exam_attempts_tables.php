<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('IN_PROGRESS');
            $table->unsignedInteger('score')->default(0);
            $table->boolean('passed')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'user_id']);
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            // No FK on purpose: grading snapshot must survive question/option deletion.
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('selected_option_id')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('points_awarded')->default(0);
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('exam_attempts');
    }
};
