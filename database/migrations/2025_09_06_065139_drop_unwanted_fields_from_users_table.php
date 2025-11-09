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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'location',
                'relationship_goal',
                'ideal_connection',
                'willing_to_relocate',
                'preferred_age_min',
                'preferred_age_max',
                'preferred_property_type',
                'identity',
                'budget_min',
                'budget_max',
                'preferred_location',
                'perfect_weekend',
                'cant_live_without',
                'quirky_fact',
                'about_me',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable();
            $table->string('location')->nullable();
            $table->string('relationship_goal')->nullable();
            $table->unsignedBigInteger('ideal_connection')->nullable();
            $table->unsignedBigInteger('willing_to_relocate')->nullable();
            $table->integer('preferred_age_min')->nullable();
            $table->integer('preferred_age_max')->nullable();
            $table->string('preferred_property_type')->nullable();
            $table->string('identity')->nullable();
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->string('preferred_location')->nullable();
            $table->text('perfect_weekend')->nullable();
            $table->text('cant_live_without')->nullable();
            $table->text('quirky_fact')->nullable();
            $table->text('about_me')->nullable();
        });
    }
};
