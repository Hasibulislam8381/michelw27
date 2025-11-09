<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            SystemSettingSeeder::class,
            SocialMediaSeeder::class,
            BlogSeeder::class,
            DynamicPagesSeeder::class,
            AdminUserSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@user.com',
            'password' => Hash::make('12345678'),
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
