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
            $query = self::select('products.*', 'product_img.img')
                ->leftJoin('product_img', 'products.skuId', '=', 'product_img.product_id');

            if (isset($inputData['skuId'])) {
                $inputData = array_merge($inputData, [
                    'q' => null,
                    'categoryId' => null,
                    'subCategoryId' => null,
                    'name' => null,
                    'description' => null,
                    'startingPrice' => null,
                    'endingPrice' => null
                ]);

                $isProductExist = $query->where('products.skuId', $inputData['skuId'])->get();

                if ($isProductExist->isNotEmpty()) {
                    /* Map images correctly */
                    $productData = $isProductExist->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'skuId' => $product->skuId,
                            'vendorId' => $product->vendorId,
                            'categoryId' => $product->categoryId,
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => $product->price,
                            'stock' => $product->stock,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                            'productImg' => $product->img ? json_decode($product->img, true) : []
                        ];
                    });

                    return app(Response::class)->success([
                        'data' => $productData
                    ]);
                } else {
                    return app(Response::class)->error(['message' => 'Product does not exist']);
                }
            }

            /* Search query */
            if (isset($inputData['q'])) {
                $query->where(function ($query) use ($inputData) {
                    $query->where('products.name', 'like', '%' . $inputData['q'] . '%')
                        ->orWhere('products.description', 'like', '%' . $inputData['q'] . '%')
                        ->orWhere('products.price', 'like', '%' . $inputData['q'] . '%');
                });
            }

            /* Price range filter */
            if (isset($inputData['startingPrice'])) {
                $maxPrice = isset($inputData['endingPrice']) ? $inputData['endingPrice'] : self::max('price');
                $query->whereBetween('products.price', [$inputData['startingPrice'], $maxPrice]);
            }

            /* Category filter */
            if (isset($inputData['categoryId'])) {
                $categoryIds = is_array($inputData['categoryId']) ? $inputData['categoryId'] : explode(',', $inputData['categoryId']);
                $query->whereIn('products.categoryId', $categoryIds);
            }

            /* Subcategory filter */
            if (isset($inputData['subCategoryId'])) {
                $subCategoryIds = is_array($inputData['subCategoryId']) ? $inputData['subCategoryId'] : explode(',', $inputData['subCategoryId']);
                $query->whereIn('products.subCategoryId', $subCategoryIds);
            }

            /* Order by date in descending order */
            $query->orderBy('products.created_at', 'desc');

            /* Set pagination parameter */
            $perPage = isset($inputData['perPage']) ? $inputData['perPage'] : 10;

            /* Apply pagination */
            $products = $query->paginate($perPage);

            /* Format the product response */
            $productResponse = collect($products->items())->map(function ($product) {
                return [
                    'id' => $product->id,
                    'skuId' => $product->skuId,
                    'vendorId' => $product->vendorId,
                    'categoryId' => $product->categoryId,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'productImg' => $product->img ? json_decode($product->img, true) : []
                ];
            });

            /* Pagination details */
            $paginationDetails = [
                'totalProducts' => $products->total(),
                'perPage' => $products->perPage(),
                'currentPage' => $products->currentPage()
            ];

            /* Prepare the response */
            return app(Response::class)->success([
                'data' => ['products' => $productResponse[0]],
                'pagination' => $paginationDetails
            ]);
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), [], 500, 'JSON');
            return $e->getMessage();
        }
    }
}
