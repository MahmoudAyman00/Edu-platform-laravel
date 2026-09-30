<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('question_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_option_id')->constrained('question_options')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->text('text');
            $table->timestamps();

            $table->unique(['question_option_id', 'locale'], 'qot_option_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_option_translations');
        Schema::dropIfExists('question_options');
    }
};
