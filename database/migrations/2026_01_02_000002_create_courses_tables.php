<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status', 20)->default('DRAFT');
            $table->timestamps();
        });

        Schema::create('course_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_translations');
        Schema::dropIfExists('courses');
    }
};
