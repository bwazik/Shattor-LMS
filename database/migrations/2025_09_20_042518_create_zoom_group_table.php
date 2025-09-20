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
        Schema::table('zooms', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::create('zoom_group', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('zoom_id')->unsigned();
            $table->integer('group_id')->unsigned();

            $table->unique(['zoom_id', 'group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zooms', function (Blueprint $table) {
            $table->integer('group_id')->unsigned();
        });

        Schema::dropIfExists('zoom_group');
    }
};
