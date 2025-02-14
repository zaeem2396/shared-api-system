<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use App\Utils\SkuId;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class ProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $faker = Faker::create();
            $categoryIds = Category::pluck('id')->toArray();
            $subCategoryIds = Category::pluck('id')->toArray();
            $vendorIds = User::where('platform_id', app('Helper')->fetchAppSettings()['vendoraPlatformId'])->pluck('id')->toArray();

            for ($i = 0; $i < 500; $i++) {
                $skuId = app(SkuId::class)->generateSkuId();
                $category = $faker->randomElement($categoryIds);
                $subCategory = $faker->randomElement($subCategoryIds);
                $vendor = $faker->randomElement($vendorIds);
                $productName = $faker->sentence(rand(2, 5));
                $productDescription = $faker->sentence(rand(20, 35));
                $productPrice = $faker->randomFloat(2, 10, 1000);
                $productQuantity = rand(1, 100);
                $productImage = $faker->imageUrl(640, 480, 'product', true);

                DB::table('products')->insert([
                    'skuId' => $skuId,
                    'vendorId' => $vendor,
                    'categoryId' => $category,
                    'subCategoryId' => $subCategory,
                    'name' => $productName,
                    'description' => $productDescription,
                    'price' => $productPrice,
                    'stock' => $productQuantity,
                    'status' => null,
                    'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                    'updated_at' => now(),
                ]);

                DB::table('product_img')->insert([
                    'product_id' => $skuId,
                    'img' => json_encode([
                        'url' => $productImage,
                        'public_id' => $faker->uuid,
                    ]),
                    'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                    'updated_at' => now(),
                ]);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
