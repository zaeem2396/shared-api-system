<?php

namespace App\Models;

use App\Utils\ActivityLogger;
use App\Utils\Response;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'id',
        'name',
        'created_at',
        'updated_at'
    ];

    public static function categoryAction(array $inputData)
    {
        try {
            switch ($inputData['method']) {
                case 'POST':
                    return self::createCategory($inputData);
                case 'PUT':
                    return self::updateCategory($inputData);
                case 'DELETE':
                    return self::deleteCategory($inputData);
                case 'GET':
                    return self::fetchCategory();
                default:
                    app(ActivityLogger::class)->logSystemActivity('Invalid method', $inputData, 400, 'JSON');
                    return app(Response::class)->error(['message' => 'Invalid method']);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    private static function createCategory(array $inputData)
    {
        try {
            $isCategoryExist = self::where('name', $inputData['name'])->exists();
            if ($isCategoryExist) {
                app(ActivityLogger::class)->logSystemActivity('Category already exist', $inputData, 409, 'JSON');
                return app(Response::class)->error(['message' => 'Category already exist']);
            }
            $category = self::create($inputData);
            app(ActivityLogger::class)->logSystemActivity('Category created successfully', $category, 201, 'JSON');
            return app(Response::class)->success(['message' => 'Category created successfully', 'data' => $category]);
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');
            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    private static function updateCategory(array $inputData)
    {
        try {
            /* Check if id is passed */
            if (!$inputData['id']) {
                app(ActivityLogger::class)->logSystemActivity('Id parameter missing', $inputData, 400, 'JSON');
                return app(Response::class)->error(['message' => 'Id parameter missing']);
            }
            $category = self::where('id', $inputData['id'])->first();
            if (!$category) {
                app(ActivityLogger::class)->logSystemActivity('Category does not exist', $inputData, 404, 'JSON');
                return app(Response::class)->error(['message' => 'Category does not exist']);
            }
            $category->update($inputData);
            app(ActivityLogger::class)->logSystemActivity('Category updated successfully', $category, 200, 'JSON');
            return app(Response::class)->success(['message' => 'Category updated successfully', 'data' => $category]);
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');
            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    private static function deleteCategory(array $inputData)
    {
        try {
            /* Check if id is passed */
            if (!$inputData['id']) {
                app(ActivityLogger::class)->logSystemActivity('Id parameter missing', $inputData, 400, 'JSON');
                return app(Response::class)->error(['message' => 'Id parameter missing']);
            }
            $category = self::where('name', $inputData['name'])->first();
            if (!$category) {
                app(ActivityLogger::class)->logSystemActivity('Category does not exist', $inputData, 404, 'JSON');
                return app(Response::class)->error(['message' => 'Category does not exist']);
            }
            $category->delete();
            app(ActivityLogger::class)->logSystemActivity('Category deleted successfully', $category, 200, 'JSON');
            return app(Response::class)->success(['message' => 'Category deleted successfully', 'data' => $category]);
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');
            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    private static function fetchCategory()
    {
        try {
            $category = self::all();
            app(ActivityLogger::class)->logSystemActivity('Category fetched successfully', $category, 200, 'JSON');
            return app(Response::class)->success(['message' => 'Category fetched successfully', 'data' => $category]);
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), [], 500, 'JSON');
            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }
}
