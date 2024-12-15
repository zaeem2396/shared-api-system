<?php

namespace App\Models;

use App\Utils\ActivityLogger;
use App\Utils\Response;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    use HasFactory;

    protected $table = 'blogCategory';

    protected $fillable = [
        'id',
        'name',
    ];

    public $timestamps = false;

    public static function createCategory(array $inputData)
    {
        try {
            app(ActivityLogger::class)->logSystemActivity('starting create blog category process', $inputData, '', '');
            // Check if category exist
            $isCategoryExist = self::where('name', $inputData['name'])->first();
            if ($isCategoryExist) {
                app(ActivityLogger::class)->logSystemActivity('category already exist', $inputData, 409, 'json');
                return app(Response::class)->duplicate(['response' => 'category already exist']);
            }
            $isCategoryCreated = self::create($inputData);
            if ($isCategoryCreated) {
                app(ActivityLogger::class)->logSystemActivity('category created successfully', $inputData, 201, 'json');
                return app(Response::class)->success(['response' => 'category created successfully']);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    public static function updateCategory(array $inputData)
    {
        try {
            app(ActivityLogger::class)->logSystemActivity('starting update category process', $inputData, '', '');
            // check if category id exist
            $isCategoryExist = self::where('id', $inputData['id'])->first();
            if (!$isCategoryExist) {
                app(ActivityLogger::class)->logSystemActivity('category id not found', $inputData['id'], '', '');
                return app(Response::class)->error(['response' => 'category id does not exist']);
            } else {
                // check if updating category already exist
                $isDuplicateCategory = self::where('name', $inputData['name'])->first();
                if ($isDuplicateCategory) {
                    app(ActivityLogger::class)->logSystemActivity('category already exist', $inputData, 409, 'json');
                    return app(Response::class)->duplicate(['response' => 'category already exist']);
                }
                $isCategoryUpdated = self::where('id', $inputData['id'])->update($inputData);
                if ($isCategoryUpdated) {
                    app(ActivityLogger::class)->logSystemActivity('category updated successfully', $inputData, 201, 'json');
                    return app(Response::class)->success(['response' => 'category updated successfully']);
                }
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }
}
