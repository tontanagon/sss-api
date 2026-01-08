<?php

namespace Database\Seeders;

use App\Models\CoreConfigs;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('core_configs')->insert([
            'name' => 'banner',
            'code' => 'banner',
            'group' => 'banner',
            'category' => 'banner',
        ]);
    }
}
