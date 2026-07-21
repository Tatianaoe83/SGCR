<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('document_chunks', function (Blueprint $table) {
            $table->longText('embedding')->nullable()->after('char_count');
            $table->string('embedding_model')->nullable()->after('embedding');
        });
    }

    public function down()
    {
        Schema::table('document_chunks', function (Blueprint $table) {
            $table->dropColumn(['embedding', 'embedding_model']);
        });
    }
};
