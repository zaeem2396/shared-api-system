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

    public static function createBlog(array $inputData)
    {
        try {
            app(ActivityLogger::class)->logSystemActivity('create blog process starts', $inputData, '', '');
            $allowedExt = ['jpg', 'png', 'jpeg'];
            if (!in_array($inputData['image']->getClientOriginalExtension(), $allowedExt)) {
                app(ActivityLogger::class)->logSystemActivity('Invalid image uploaded, process terminates', $inputData, 500, '');
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
                app(ActivityLogger::class)->logSystemActivity('blog created successfully', $inputData, 200, 'json');
                return app(Response::class)->success(['response' => 'Blog created successfully']);
            } else {
                app(ActivityLogger::class)->logSystemActivity('blog creation failed', $inputData, 500, 'json');
                return app(Response::class)->error(['response' => 'Blog creation failed']);
            }
        } catch (Exception $e) {
            // Log the error
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    public static function updateBlog(array $inputData)
    {
        try {
            app(ActivityLogger::class)->logSystemActivity('update blog process starts', $inputData, '', '');

            $isBlogExist = self::where('id', $inputData['id'])->first();
            if (!$isBlogExist) {
                app(ActivityLogger::class)->logSystemActivity('blog not found', $inputData, 404, '');
                return app(Response::class)->error(['response' => 'Blog not found']);
            }
            $allowedExt = ['jpg', 'png', 'jpeg'];
            if (!in_array($inputData['image']->getClientOriginalExtension(), $allowedExt)) {
                app(ActivityLogger::class)->logSystemActivity('Invalid image uploaded, process terminates', $inputData, 500, '');
                return app(Response::class)->error(['response' => 'Invalid image type, please upload only ' . implode(',', $allowedExt)]);
            }

            if ($inputData['image']) {
                app(Cloudinary::class)->delete($isBlogExist->publicId);
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
            $isBlogUpdated = self::where('id', $inputData['id'])->update($inputData);
            if ($isBlogUpdated) {
                app(ActivityLogger::class)->logSystemActivity('blog updated successfully', $inputData, 200, 'json');
                return app(Response::class)->success(['response' => 'Blog updated successfully']);
            } else {
                app(ActivityLogger::class)->logSystemActivity('blog update failed', $inputData, 500, 'json');
                return app(Response::class)->error(['response' => 'Blog update failed']);
            }
        } catch (Exception $e) {
            // Log the error
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    public static function fetchBlogs(array $inputData)
    {
        try {
            $query = self::select('*');

            // If id is passed set rest of the parameters as NULL
            if (isset($inputData['id']) && $inputData['id']) {
                $inputData['s'] = null;
                $inputData['region'] = null;
                $inputData['categoryId'] = null;
                $inputData['date'] = null;
                $inputData['perPage'] = null;
                $isBlogExist = self::where('id', $inputData['id'])->first();
                if ($isBlogExist) {
                    $isBlogExist->authorId = json_decode($isBlogExist->authorId, true);
                    $isBlogExist->categoryId = json_decode($isBlogExist->categoryId, true);
                    return app(Response::class)->success(['blog' => $isBlogExist]);
                } else {
                    return app(Response::class)->error(['response' => 'Blog not found']);
                }
            }

            // Apply search filters
            if (isset($inputData['s']) && $inputData['s']) {
                $query->where(function ($query) use ($inputData) {
                    $query->where('title', 'like', '%' . $inputData['s'] . '%')
                        ->orWhere('summary', 'like', '%' . $inputData['s'] . '%');
                });
            }

            // Apply filters if they exist
            if (isset($inputData['region']) && $inputData['region']) {
                $query->where('region', $inputData['region']);
            }

            if (isset($inputData['categoryId']) && $inputData['categoryId']) {
                $query->whereJsonContains('categoryId', $inputData['categoryId']);
            }

            // Order by date in descending order
            $query->orderBy('created_at', 'desc');

            // Set pagination parameters
            $perPage = $inputData['perPage'] ?? 10;

            // Apply pagination
            $fetchBlogs = $query->paginate($perPage);

            // Transform the collection
            $fetchBlogs->getCollection()->transform(function ($blog) {
                $blog->authorId = json_decode($blog->authorId, true);
                $blog->categoryId = json_decode($blog->categoryId, true);
                return $blog;
            });

            // Pagination Details
            $paginationDetails = [
                'totalRecords' => $fetchBlogs->total(),
                'currentPage' => $fetchBlogs->currentPage(),
                'perPageRecords' => $fetchBlogs->perPage(),
            ];

            return app(Response::class)->success([
                'blogList' => $fetchBlogs->items(),
                'pagination' => $paginationDetails
            ]);
        } catch (Exception $e) {
            // Log the error
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => ''], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    public static function fetchTodaysBlogsByCategory(array $inputData)
    {
        try {
            $todaysBlog = self::where('categoryId', 'LIKE', "%{$inputData['categoryId']}%")->limit($inputData['limit'])->get();
            $todaysBlog->transform(function ($blog) {
                $blog->authorId = json_decode($blog->authorId, true);
                $blog->categoryId = json_decode($blog->categoryId, true);
                return $blog;
            });
            return app(Response::class)->success(['blogList' => $todaysBlog]);
        } catch (Exception $e) {
            // Log the error
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => ''], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }
}
