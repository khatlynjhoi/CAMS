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
            $table->string('program')->nullable();
            $table->string('term')->nullable();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('course_outcome_id')->nullable();
            $table->string('learning_outcome_id')->nullable();
            $table->string('code')->unique();
            $table->string('type');
            $table->text('text');
            $table->text('image_url')->nullable();
            $table->json('options')->nullable();
            $table->text('correct_answer')->nullable();
            $table->json('matching_pairs')->nullable();
            $table->string('stcw_standard')->nullable();
            $table->string('bloom_level')->nullable();
            $table->string('status')->default('Draft');
            $table->boolean('ai_accepted')->default(false);
            $table->boolean('retained_ai')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};