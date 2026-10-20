<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('cadet_id')->nullable();
            $table->string('cadet_name');
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('course_code')->nullable();
            $table->decimal('score', 8, 2)->default(0);
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->json('breakdown')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_submissions');
    }
};