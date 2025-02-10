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
        'subCategoryId',
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

    public function fetchProduct(array $inputData)
    {
        try {
            $query = self::select('*');
            if (isset($inputData['skuId'])) {
                $inputData['q'] = null;
                $inputData['categoryId'] = null;
                $inputData['subCategoryId'] = null;
                $inputData['name'] = null;
                $inputData['description'] = null;
                $inputData['startingPrice'] = null;
                $inputData['endingPrice'] = null;
                $isProductExist = self::where('skuId', $inputData['skuId'])->first();
                if ($isProductExist) {
                    $productImg = ProductImage::select('img')->where('product_id', $inputData['skuId'])->get()->map(function ($img) {
                        return json_decode($img->img, true);
                    });
                    return app(Response::class)->success(['data' => $isProductExist, 'productImg' => $productImg]);
                } else {
                    return app(Response::class)->error(['message' => 'Product does not exist']);
                }
            }

            /* Search query */
            if (isset($inputData['q'])) {
                $query->where(function ($query) use ($inputData) {
                    $query->where('name', 'like', '%' . $inputData['q'] . '%')
                        ->orWhere('description', 'like', '%' . $inputData['q'] . '%')
                        ->orWhere('price', 'like', '%' . $inputData['q'] . '%');
                });
            }

            /* Price range filter */
            if (isset($inputData['startingPrice'])) {
                $maxPrice = isset($inputData['endingPrice'])
                    ? $inputData['endingPrice']
                    : self::max('price'); // Get the maximum price from the products table

                $query->whereBetween('price', [$inputData['startingPrice'], $maxPrice]);
            }

            /* Category filter */
            if (isset($inputData['categoryId'])) {
                $categoryIds = is_array($inputData['categoryId'])
                    ? $inputData['categoryId']
                    : explode(',', $inputData['categoryId']); // Convert string to array

                $query->whereIn('categoryId', $categoryIds);
            }

            /* Subcategory filter */
            if (isset($inputData['subCategoryId'])) {
                $subCategoryIds = is_array($inputData['subCategoryId'])
                    ? $inputData['subCategoryId']
                    : explode(',', $inputData['subCategoryId']); // Convert string to array

                $query->whereIn('subCategoryId', $subCategoryIds);
            }

            /* Order by date in descending order */
            $query->orderBy('created_at', 'desc');

            /* Set paginaion parameter */
            $perPage = isset($inputData['perPage']) ? $inputData['perPage'] : 10;

            /* Apply pagination */
            $products = $query->paginate($perPage);

            /* Transform images */
            $productImg = ProductImage::select('img')->get()->map(function ($img) {
                return json_decode($img->img, true);
            })->toArray();

            $productImg = !empty($productImg) ? $productImg : null;

            /* Pagination details */
            $paginationDetails = [
                'totalProducts' => $products->total(),
                'perPage' => $products->perPage(),
                'currentPage' => $products->currentPage() ?? $inputData['currentPage'],
            ];
            /* Prepare the response */
            $productResponse = [
                'products' => $products->items(),
                'productImg' => $productImg
            ];
            if ($products) {
                return app(Response::class)->success([
                    'data' => $productResponse,
                    'pagination' => $paginationDetails
                ]);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), [], 500, 'JSON');

            return $e->getMessage();
        }
    }
}
