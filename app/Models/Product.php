<?php

namespace App\Models;

use App\Utils\ActivityLogger;
use App\Utils\ImageKit;
use App\Utils\Response;
use App\Utils\SkuId;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'id',
        'skuId',
        'vendorId',
        'categoryId',
        'name',
        'description',
        'price',
        'stock',
        'status',
        'created_at',
        'updated_at'
    ];

    public function createProduct(array $inputData)
    {
        try {
            $vendorExist = Vendors::where('userId', $inputData['vendorId'])->exists();
            /* Chech if vendor exist */
            if (!$vendorExist) {
                app(ActivityLogger::class)->logSystemActivity('Vendor does not exist', $inputData, 404, 'JSON');
                return app(Response::class)->error(['message' => 'Vendor does not exist']);
            }
            $isCategoryExist = Category::where('id', $inputData['categoryId'])->exists();
            /* Check if category exist */
            if (!$isCategoryExist) {
                app(ActivityLogger::class)->logSystemActivity('Category does not exist', $inputData, 404, 'JSON');
                return app(Response::class)->error(['message' => 'Category does not exist']);
            }
            if ($inputData['price'] < 0 && $inputData['stock'] < 0) {
                app(ActivityLogger::class)->logSystemActivity('Negative value entered', $inputData, 500, 'JSON');
                return app(Response::class)->error(['message' => 'system error occured']);
            }
            /* Check if image is a valid image */
            $allowedExt = ['jpg', 'png', 'jpeg'];
            if (!in_array($inputData['img']->getClientOriginalExtension(), $allowedExt)) {
                app(ActivityLogger::class)->logSystemActivity('Invalid image uploaded, process terminates', $inputData, 500, '');
                return app(Response::class)->error(['response' => 'Invalid image type, please upload only ' . implode(',', $allowedExt)]);
            }
            $imgKitRes = app(ImageKit::class)->uploadToImgKit($inputData['img']);
            $inputData['img'] = json_encode([
                'url' => $imgKitRes['url'],
                'public_id' => $imgKitRes['public_id']
            ]);
            $inputData['skuId'] = app(SkuId::class)->generateSkuId();
            $inputData['status'] = null;
            $isProductImageUploaded = ProductImage::create(['product_id' => $inputData['skuId'], 'img' => $inputData['img']]);
            if (!$isProductImageUploaded) {
                app(ActivityLogger::class)->logSystemActivity('Product image not uploaded', $inputData, 500, 'JSON');
                return app(Response::class)->error(['message' => 'system error occured']);
            }
            $isProductCreated = self::create($inputData);
            if ($isProductCreated) {
                return app(Response::class)->success(['message' => 'Product created successfully']);
            } else {
                return app(Response::class)->error(['message' => 'system error occured']);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), $inputData, 500, 'JSON');

            return $e->getMessage();
        }
    }

    public function fetchProduct()
    {
        try {
            /* Need to optomize this code */
            $products = self::all()->toArray();
            $productImg = ProductImage::select('img')->get()->map(function ($img) {
                return json_decode($img->img, true);
            });
            $productResponse = [
                'products' => $products,
                'productImg' => $productImg
            ];
            if ($products) {
                return app(Response::class)->success(['data' => $productResponse]);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), [], 500, 'JSON');

            return $e->getMessage();
        }
    }
}
