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
        Schema::create('resource_views', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('resource_id')->unsigned();
            $table->integer('student_id')->unsigned();
            $table->integer('views')->default(0);
            $table->integer('duration_watched')->default(0);
            $table->integer('percent_watched')->default(0);
            $table->boolean('is_banned')->default(false);
            $table->timestamp('first_watched_at')->nullable();
            $table->timestamp('last_watched_at')->nullable();
            $table->unique(['resource_id', 'student_id']);
            $table->timestamps();

            $table->foreign('resource_id')->references('id')->on('resources')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        Schema::create('resource_video_events', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('resource_id')->unsigned();
            $table->integer('student_id')->unsigned();
            $table->string('event_type');
            $table->json('data');
            $table->integer('timestamp')->nullable();
            $table->timestamps();

            $table->foreign('resource_id')->references('id')->on('resources')
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
        Schema::dropIfExists('resource_analytics_tables');
    }
};
