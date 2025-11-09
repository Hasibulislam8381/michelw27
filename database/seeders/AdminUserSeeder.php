<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@admin.com'], // যদি আগে থাকে, update করবে
            [
                'name' => 'Admin',
                'password' => Hash::make('12345678'),
                'email_verified_at' => Carbon::now(),
            ]
        );
    }
}
