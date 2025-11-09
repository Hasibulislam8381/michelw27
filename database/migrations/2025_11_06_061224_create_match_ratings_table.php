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
        Schema::create('match_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caption_id');
            $table->unsignedBigInteger('match_id');
            $table->unsignedBigInteger('team_id');
            $table->enum('entity_type', ['coach', 'player']);
            $table->unsignedBigInteger('entity_id');
            $table->string('name');
            $table->string('photo')->nullable();
            $table->decimal('rating', 3, 1)->default(0.0); // e.g. 7.5
            $table->boolean('is_mom')->default(false);
            $table->timestamps();

            $table->foreign('caption_id')->references('id')->on('match_rating_captions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_ratings');
    }
};
