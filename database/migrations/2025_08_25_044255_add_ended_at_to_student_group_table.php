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
        Schema::table('student_group', function (Blueprint $table) {
            $table->timestamp('ended_at')->nullable()->after('updated_at');

            $table->index('ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_group', function (Blueprint $table) {
            $table->dropColumn('ended_at');
        });
    }
};
