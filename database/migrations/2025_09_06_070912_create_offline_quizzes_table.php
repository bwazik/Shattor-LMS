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
        Schema::create('offline_quizzes', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique();
            $table->integer('teacher_id')->unsigned();
            $table->integer('grade_id')->unsigned();
            $table->string('name');
            $table->tinyInteger('type')->default(1)->comment('1 => mini quiz, 2 => exam');
            $table->integer('score')->default(100)->comment('Maximum score for the quiz');
            $table->date('conducted_at')->nullable()->comment('Date the quiz was conducted');
            $table->timestamps();

            $table->index(['teacher_id', 'grade_id', 'type']);
        });

        Schema::table('offline_quizzes', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('grade_id')->references('id')->on('grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_quizzes');
    }
};
