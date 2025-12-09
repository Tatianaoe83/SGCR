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
        Schema::table('puesto_trabajos', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropColumn('area_id');

            $table->json('areas_ids')->after('unidad_negocio_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puesto_trabajos', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable();

            $table->foreign('ara_id')
                ->references('id_area')
                ->on('area')
                ->cascadeOnDelete();

            $table->dropColumn('areas_ids');
        });
    }
};
