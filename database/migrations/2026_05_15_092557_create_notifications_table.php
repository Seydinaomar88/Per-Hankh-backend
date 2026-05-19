<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /**
             * auteur action
             */
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            /**
             * type notification
             */
            $table->string('type');

            /**
             * contenu
             */
            $table->text('message');

            /**
             * lu ou non
             */
            $table->boolean('is_read')
                ->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
