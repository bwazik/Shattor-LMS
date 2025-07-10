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
        Schema::create('grade_fees', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique();
            $table->integer('teacher_id')->unsigned();
            $table->integer('grade_id')->unsigned();
            $table->decimal('amount')->default(0.00);
            $table->string('month')->nullable()->comment('YYYY-MM format for specific month, null for default');
            $table->timestamps();
        });

        Schema::table('grade_fees', function (Blueprint $table) {
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
        Schema::dropIfExists('grade_fees');

        Schema::table('grade_fees', function (Blueprint $table) {
            $table->dropForeign('grade_fees_teacher_id_foreign');
            $table->dropForeign('grade_fees_grade_id_foreign');
        });
    }
};
