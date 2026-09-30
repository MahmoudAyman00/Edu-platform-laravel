<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('merchant_ref')->unique()->comment('Our reference sent to Fawry');
            $table->string('fawry_reference')->nullable()->comment('Fawry-side reference number');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EGP');
            $table->string('method', 20);
            $table->string('status', 20)->default('PENDING');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
