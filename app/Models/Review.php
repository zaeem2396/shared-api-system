<?php

namespace App\Models;

use App\Models\{User, Blog};
use App\Utils\ActivityLogger;
use App\Utils\Blasp;
use App\Utils\Nlp;
use App\Utils\Response;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Review extends Model
{
    use HasFactory;

    protected $table = 'review';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'blog_id',
        'rating',
        'comment',
        'sentimental_score'
    ];


    public static function submitReview(array $inputData)
    {
        try {
            $isUserExist = User::where('id', $inputData['user_id'])->first();
            $isBlogExist = Blog::where('id', $inputData['blog_id'])->first();
            if (!$isUserExist) {
                return app(Response::class)->error(['message' => 'User not found']);
            }
            if (!$isBlogExist) {
                return app(Response::class)->error(['message' => 'Blog not found']);
            }
            if (!$isUserExist->isEmailVerified == 1) {
                return app(Response::class)->error(['message' => 'User not verified']);
            }
            $inputData['sentimental_score'] = app(Nlp::class)->sentimentalScore($inputData['comment']);
            $inputData['comment'] = app(Blasp::class)->blaspHelper($inputData['comment']);
            $isReviewSubmitted = self::create($inputData);
            if ($isReviewSubmitted) {
                return app(Response::class)->success(['message' => 'Review submitted successfully']);
            } else {
                return app(Response::class)->error(['message' => 'Review not submitted']);
            }
        } catch (Exception $e) {
            // Log the error
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }

    public static function fetchReviews(array $inputData)
    {
        try {
            $query = DB::table('review')
                ->join('users', 'review.user_id', '=', 'users.id')
                ->join('blogs', 'review.blog_id', '=', 'blogs.id')
                ->select('review.*', 'users.name as user_name', 'blogs.title as blog_title');
            if (isset($inputData['user_id'])) {
                $isUserExist = User::where('id', $inputData['user_id'])->first();
                if (!$isUserExist) {
                    return app(Response::class)->error(['message' => 'User not found']);
                }
                $query->where('review.user_id', $inputData['user_id']);
            } elseif (isset($inputData['blog_id'])) {
                $isBlogExist = Blog::where('id', $inputData['blog_id'])->first();
                if (!$isBlogExist) {
                    return app(Response::class)->error(['message' => 'Blog not found']);
                }
                $query->where('review.blog_id', $inputData['blog_id']);
            } else {
                return app(Response::class)->error(['message' => 'Invalid request']);
            }
            $reviews = $query->get();
            return app(Response::class)->success(['reviews' => $reviews]);
        } catch (Exception $e) {
            // Log the error
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }
}
