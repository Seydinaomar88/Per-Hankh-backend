<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {

            $table->id();

            $table->foreignId('workspace_id')
                ->constrained()
                ->cascadeOnDelete();

            /**
             * tâche optionnelle
             */
            $table->foreignId('task_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /**
             * note optionnelle
             */
            $table->foreignId('note_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete();

            /**
             * infos fichier
             */
            $table->string('original_name');

            $table->string('file_name');

            $table->string('mime_type');

            $table->unsignedBigInteger('size');

            $table->string('path');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};