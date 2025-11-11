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
        Schema::table('match_rating_captions', function (Blueprint $table) {
            $table->string('home_team_name')->nullable()->after('id');
            $table->string('away_team_name')->nullable()->after('home_team_name');
        });
    }

    public function down(): void
    {
        Schema::table('match_rating_captions', function (Blueprint $table) {
            $table->dropColumn(['home_team_name', 'away_team_name']);
        });
    }
};
