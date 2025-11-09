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
        Schema::table('email_otps', function (Blueprint $table) {
            $table->string('phone_code')->nullable()->after('email'); // ইচ্ছে করলে position পরিবর্তন করা যাবে
            $table->string('phone')->nullable()->after('phone_code');
        });
    }

    public function down(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->dropColumn(['phone_code', 'phone']);
        });
    }
};
