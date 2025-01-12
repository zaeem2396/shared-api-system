<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $faker = Faker::create();

            $userIds = [1, 2, 3, 4, 5];

            $blogId = range(1, 250);

            for ($i = 0; $i < 500; $i++) {
                $rating = rand(1, 5);
                $comment = $faker->sentence(rand(6, 15));
                $sentimentalScore = mt_rand(10, 100) / 100;

                DB::table('review')->insert([
                    'user_id' => $faker->randomElement($userIds),
                    'blog_id' => $faker->randomElement($blogId),
                    'rating' => $rating,
                    'comment' => $comment,
                    'sentimental_score' => $sentimentalScore,
                    'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                    'updated_at' => now(),
                ]);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
