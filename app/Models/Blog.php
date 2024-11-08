<?php

namespace App\Models;

use Exception;
use App\Utils\Cloudinary;
use App\Models\User;
use App\Models\BlogCategory;
use App\Utils\ActivityLogger;
use App\Utils\Response;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    protected $table = 'blogs';

    protected $fillable = [
        'authorId',
        'categoryId',
        'title',
        'summary',
        'image',
        'publicId',
        'region'
    ];

    public $timestamps = false;

    public static function createBlog(array $inputData)
    {
        try {
            $allowedExt = ['jpg', 'png', 'jpeg'];
            if (!in_array($inputData['image']->getClientOriginalExtension(), $allowedExt)) {
                return app(Response::class)->error(['response' => 'Invalid image type, please upload only ' . implode(',', $allowedExt)]);
            }

            // Convert 'authorId' to an array of IDs
            $authorIds = explode(',', $inputData['authorId']);

            // Fetch the existing authors with both ID and name
            $existingAuthors = User::whereIn('id', $authorIds)->get(['id', 'name']);
            $foundIds = $existingAuthors->pluck('id')->toArray();

            // Identify missing IDs
            $missingIds = array_diff($authorIds, $foundIds);

            if (!empty($missingIds)) {
                return app(Response::class)->error([
                    'response' => 'No authors found for the following IDs: ' . implode(', ', $missingIds)
                ]);
            }
            // If all IDs are valid, return the names of author
            $inputData['authorId'] = json_encode($existingAuthors->pluck('name')->all());

            $categorydId = explode(',', $inputData['categoryId']);

            // Fetch the existing ccategory with ID and name
            $existingCategories = BlogCategory::whereIn('id', $categorydId)->get(['id', 'name']);
            $foundCatIds = $existingCategories->pluck('id')->toArray();

            // Capture missing IDs
            $missingCatIds = array_diff($categorydId, $foundCatIds);
            if (!empty($missingCatIds)) {
                return app(Response::class)->error([
                    'response' => 'No categories found for the following IDs: ' . implode(', ', $missingCatIds)
                ]);
            }
            $inputData['categoryId'] = json_encode($existingCategories->pluck('name')->all());
            $blogImgUpload = app(Cloudinary::class)->store($inputData['image']);
            $inputData['image'] = $blogImgUpload['url'];
            $inputData['publicId'] = $blogImgUpload['public_id'];
            $isBlogCreated = self::create($inputData);
            if ($isBlogCreated) {
                return app(Response::class)->success(['response' => 'Blog created successfully']);
            } else {
                return app(Response::class)->error(['response' => 'Blog creation failed']);
            }
        } catch (Exception $e) {
            // Log the error
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }
}
