<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class BlogTableSeeder extends Seeder
{

public function run(): void
{
    try {
        $faker = Faker::create();

        // Generate pairs for authorIds and categoryIds
        $authorPairs = [
            ["john doe", "tyler james"],
            ["john doe", "john cena"],
            ["john doe", "zaeem ansari"],
            ["john doe", "jason juan"],
            ["tyler james", "john cena"],
            ["tyler james", "zaeem ansari"],
            ["tyler james", "jason juan"],
            ["john cena", "zaeem ansari"],
            ["john cena", "jason juan"],
            ["zaeem ansari", "jason juan"]
        ];
        
        $categoryPairs = [
            ["crime", "politics"],
            ["crime", "sports"],
            ["crime", "business"],
            ["politics", "sports"],
            ["politics", "business"],
            ["sports", "business"]
        ];

        for ($i = 0; $i < 250; $i++) {
            DB::table('blogs')->insert([
                'authorId' => json_encode($faker->randomElement($authorPairs)), // select random pair of authors
                'categoryId' => json_encode($faker->randomElement($categoryPairs)), // select random pair of categories
                'title' => $faker->sentence(6),
                'summary' => '<div>' . $faker->paragraph(350, true) . '</div>',
                'image' => $faker->imageUrl(800, 400, 'business', true, 'Blog Image'),
                'publicId' => 'newzy/' . $faker->uuid,
                'region' => $faker->randomElement(['en', 'in', 'fr', 'es']),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ]);
        }
        echo "Data populated successfully with 250 records.\n";
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

}
