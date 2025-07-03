<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUuidIndexesToTables extends Migration
{
    protected $tables = [
        'students' => 'uuid',
        'parents' => 'uuid',
        'teachers' => 'uuid',
        'assistants' => 'uuid',
    ];

    public function up()
    {
        foreach ($this->tables as $tableName => $column) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $column) {
                $indexName = $tableName . '_' . $column . '_index';
                if (!Schema::hasIndex($tableName, $indexName)) {
                    $table->index($column);
                }
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $table => $column) {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropIndex([$column]);
            });
        }
    }
}