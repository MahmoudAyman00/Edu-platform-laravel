<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('DRAFT');
            $table->boolean('is_final')->default(false);
            $table->unsignedInteger('pass_score')->default(60);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('exam_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_translations');
        Schema::dropIfExists('exams');
    }
};
