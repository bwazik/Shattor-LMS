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
        Schema::table('students', function (Blueprint $table) {
            $table->tinyInteger('specialization')->default(1)->nullable()->after('grade_id')->comment('1 => scientific, 2 => literary');
        });

        Schema::table('grade_fees', function (Blueprint $table) {
            $table->tinyInteger('specialization')->default(1)->nullable()->after('grade_id')->comment('1 => scientific, 2 => literary');
            $table->boolean('applies_to_all_specializations')->default(true)->after('specialization');
        });

        Schema::table('fees', function (Blueprint $table) {
            $table->tinyInteger('specialization')->default(1)->nullable()->after('grade_id')->comment('1 => scientific, 2 => literary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('specialization');
        });

        Schema::table('grade_fees', function (Blueprint $table) {
            $table->dropColumn('specialization');
            $table->dropColumn('applies_to_all_specializations');
        });

        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn('specialization');
        });
    }
};
