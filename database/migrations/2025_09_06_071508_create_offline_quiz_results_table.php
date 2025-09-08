<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('offline_quiz_results', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique();
            $table->integer('student_id')->unsigned();
            $table->integer('offline_quiz_id')->unsigned();
            $table->decimal('total_score', 5, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['offline_quiz_id', 'student_id']);
            $table->index(['student_id', 'offline_quiz_id']);
        });

        Schema::table('offline_quiz_results', function (Blueprint $table) {
            $table->foreign('offline_quiz_id')->references('id')->on('offline_quizzes')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_quiz_results');
    }
};
