<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compensatory_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique();
            $table->integer('student_id')->unsigned();
            $table->integer('original_lesson_id')->unsigned();
            $table->integer('makeup_lesson_id')->unsigned();
            $table->text('reason');
            $table->tinyInteger('status')->default(1)->comment('1 => pending, 2 => approved, 3 => rejected');
            $table->timestamps();
        });

        Schema::table('compensatory_requests', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('original_lesson_id')->references('id')->on('lessons')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('makeup_lesson_id')->references('id')->on('lessons')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compensatory_requests');

        Schema::table('compensatory_requests', function (Blueprint $table) {
            $table->dropForeign('compensatory_requests_student_id_foreign');
            $table->dropForeign('compensatory_requests_original_lesson_id_foreign');
            $table->dropForeign('compensatory_requests_makeup_lesson_id_foreign');
        });
    }
};
