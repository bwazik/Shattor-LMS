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
        Schema::create('coupons', function (Blueprint $table) {
			$table->increments('id');
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->decimal('amount')->default(0.00);
            $table->boolean('is_used')->default(false);
            $table->integer('teacher_id')->unsigned()->nullable();
            $table->integer('student_id')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
