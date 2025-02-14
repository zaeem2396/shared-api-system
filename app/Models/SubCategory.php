<?php

namespace App\Models;

use App\Utils\ActivityLogger;
use App\Utils\Response;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    protected $table = 'subCategories';
    protected $fillable = [
        'id',
        'name',
        'categoryId',
        'created_at',
        'updated_at'
    ];

    public static function SubCategoryAction(array $inputData)
    {
        try {
            switch ($inputData['method']) {
                case 'POST':
                    return self::createSubCategory($inputData);
                case 'PUT':
                    return self::updateSubCategory($inputData);
                case 'DELETE':
                    return self::deleteSubCategory($inputData);
                case 'GET':
                    return self::fetchSubCategory();
                default:
                    app(ActivityLogger::class)->logSystemActivity('Invalid method', $inputData, 400, 'JSON');
                    return app(Response::class)->error(['message' => 'Invalid method']);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    private static function createSubCategory(array $inputData)
    {
        try {
            /* Check if category exist */
            $isCategoryExist = Category::where('id', $inputData['categoryId'])->exists();
            if (!$isCategoryExist) {
                app(ActivityLogger::class)->logSystemActivity('Category does not exist', $inputData, 404, 'JSON');
                return app(Response::class)->error(['message' => 'Category does not exist']);
            }
            $isSubCategoryExist = self::where('name', $inputData['name'])->exists();
            if ($isSubCategoryExist) {
                app(ActivityLogger::class)->logSystemActivity('SubCategory already exist', $inputData, 409, 'JSON');
                return app(Response::class)->error(['message' => 'SubCategory already exist']);
            }
            $subCategory = self::create($inputData);
            app(ActivityLogger::class)->logSystemActivity('SubCategory created successfully', $subCategory, 201, 'JSON');
            return app(Response::class)->success(['message' => 'SubCategory created successfully', 'data' => $subCategory]);
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');
            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    private static function updateSubCategory(array $inputData)
    {
        try {
            /* Check if id is passed */
            if (!$inputData['id']) {
                app(ActivityLogger::class)->logSystemActivity('SubCategory id is required', $inputData, 400, 'JSON');
                return app(Response::class)->error(['message' => 'SubCategory id is required']);
            }
            $isSubCategoryExist = self::where('id', $inputData['id'])->exists();
            if (!$isSubCategoryExist) {
                app(ActivityLogger::class)->logSystemActivity('SubCategory does not exist', $inputData, 404, 'JSON');
                return app(Response::class)->error(['message' => 'SubCategory does not exist']);
            }
            $subCategory = self::where('id', $inputData['id'])->update($inputData);
            app(ActivityLogger::class)->logSystemActivity('SubCategory updated successfully', $subCategory, 200, 'JSON');
            return app(Response::class)->success(['message' => 'SubCategory updated successfully', 'data' => $subCategory]);
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');
            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    private static function deleteSubCategory(array $inputData)
    {
        try {
            /* Check if id is passed */
            if (!$inputData['id']) {
                app(ActivityLogger::class)->logSystemActivity('SubCategory id is required', $inputData, 400, 'JSON');
                return app(Response::class)->error(['message' => 'SubCategory id is required']);
            }
            $isSubCategoryExist = self::where('id', $inputData['id'])->exists();
            if (!$isSubCategoryExist) {
                app(ActivityLogger::class)->logSystemActivity('SubCategory does not exist', $inputData, 404, 'JSON');
                return app(Response::class)->error(['message' => 'SubCategory does not exist']);
            }
            $subCategory = self::where('id', $inputData['id'])->delete();
            app(ActivityLogger::class)->logSystemActivity('SubCategory deleted successfully', $subCategory, 200, 'JSON');
            return app(Response::class)->success(['message' => 'SubCategory deleted successfully', 'data' => $subCategory]);
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');
            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    private static function fetchSubCategory()
    {
        try {
            $subCategory = self::all('id', 'name', 'categoryId');
            app(ActivityLogger::class)->logSystemActivity('SubCategory fetched successfully', $subCategory, 200, 'JSON');
            return app(Response::class)->success(['message' => 'SubCategory fetched successfully', 'data' => $subCategory]);
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), [], 500, 'JSON');
            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }
}
