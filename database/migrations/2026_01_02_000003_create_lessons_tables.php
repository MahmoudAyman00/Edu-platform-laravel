<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('video_path')->nullable();
            $table->unsignedInteger('duration')->nullable()->comment('Duration in seconds');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lesson_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_translations');
        Schema::dropIfExists('lessons');
    }
};
