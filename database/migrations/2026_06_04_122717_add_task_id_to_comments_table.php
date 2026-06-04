<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('task_id')->nullable()->after('note_id')->constrained()->onDelete('cascade');
            // Rendre note_id nullable
            $table->foreignId('note_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropColumn('task_id');
            $table->foreignId('note_id')->nullable(false)->change();
        });
    }
};