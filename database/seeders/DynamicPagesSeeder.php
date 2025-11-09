<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DynamicPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'page_title'   => 'About Us',
                'page_slug'    => Str::slug('About Us'),
                'page_content' => '<p>This is the About Us page content.</p>',
                'status'       => 'active',
            ],
            [
                'page_title'   => 'Privacy Policy',
                'page_slug'    => Str::slug('Privacy Policy'),
                'page_content' => '<p>This is the Privacy Policy content.</p>',
                'status'       => 'active',
            ],
            [
                'page_title'   => 'Terms & Conditions',
                'page_slug'    => Str::slug('Terms & Conditions'),
                'page_content' => '<p>This is the Terms & Conditions content.</p>',
                'status'       => 'active',
            ],
        ];

        DB::table('dynamic_pages')->insert($pages);
    }
}
