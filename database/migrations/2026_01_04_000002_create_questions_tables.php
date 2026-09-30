<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('points')->default(1);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('question_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->text('text');
            $table->timestamps();

            $table->unique(['question_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_translations');
        Schema::dropIfExists('questions');
    }
};
