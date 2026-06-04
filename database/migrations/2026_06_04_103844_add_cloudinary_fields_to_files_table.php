<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('cloudinary_url')->nullable()->after('path');
            $table->string('cloudinary_public_id')->nullable()->after('cloudinary_url');
        });
    }

    public function down()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn(['cloudinary_url', 'cloudinary_public_id']);
        });
    }
};