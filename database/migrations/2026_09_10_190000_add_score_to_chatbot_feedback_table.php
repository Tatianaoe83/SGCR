<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_feedback', function (Blueprint $table) {
            if (!Schema::hasColumn('chatbot_feedback', 'score')) {
                $table->unsignedTinyInteger('score')->nullable()->after('helpful');
            }
            if (!Schema::hasColumn('chatbot_feedback', 'session_id')) {
                $table->string('session_id', 80)->nullable()->after('score')->index();
            }
            if (!Schema::hasColumn('chatbot_feedback', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('session_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_feedback', function (Blueprint $table) {
            if (Schema::hasColumn('chatbot_feedback', 'user_id')) {
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('chatbot_feedback', 'session_id')) {
                $table->dropColumn('session_id');
            }
            if (Schema::hasColumn('chatbot_feedback', 'score')) {
                $table->dropColumn('score');
            }
        });
    }
};
