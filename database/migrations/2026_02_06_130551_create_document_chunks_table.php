<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('word_document_id')->constrained()->cascadeOnDelete();
            $table->string('section_title')->nullable();
            $table->string('chunk_type')->index();
            $table->longText('content');
            $table->integer('char_count');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_chunks');
    }
};
