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
        Schema::create('offline_quiz_group', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('offline_quiz_id')->unsigned();
            $table->integer('group_id')->unsigned();

            $table->unique(['offline_quiz_id', 'group_id']);
        });

        Schema::table('offline_quiz_group', function (Blueprint $table) {
            $table->foreign('offline_quiz_id')->references('id')->on('offline_quizzes')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('group_id')->references('id')->on('groups')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_quiz_group');
    }
};
