<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('games')->insert([
            'name' => "Hollow Knight",
            'description' => "GOTY",
            'amount' => 180
        ]);
        DB::table('games')->insert([
            'name' => "Tony Hawk",
            'description' => "Skate Game",
            'amount' => 100
        ]);
        DB::table('games')->insert([
            'name' => "Puzzle",
            'description' => "Free Movile Game",
            'amount' => 0
        ]);
    }
}
