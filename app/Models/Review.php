<?php

namespace App\Models;

use App\Utils\ActivityLogger;
use App\Utils\Blasp;
use App\Utils\Gemini;
use App\Utils\Response;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Review extends Model
{
    use HasFactory;

    protected $table = 'review';

    protected $fillable = [
        'user_id',
        'blog_id',
        'rating',
        'comment',
        'sentimental_score'
    ];

    public $timestamps = false;

    public static function create(array $inputData)
    {
        try {
            $isUserExist = User::where('id', $inputData['user_id'])->first();
            if (!$isUserExist) {
                return app(Response::class)->error(['message' => 'User not found']);
            }
            if (!$isUserExist->isEmailVerified == 1) {
                return app(Response::class)->error(['message' => 'User not verified']);
            }
            $inputData['comment'] = app(Blasp::class)->blaspHelper($inputData['comment']);
            $inputData['sentimental_score'] = app(Gemini::class)->sentimentalScore($inputData['comment']);
            $isReviewSubmitted = DB::table('review')->insert($inputData);
            if ($isReviewSubmitted) {
                return app(Response::class)->success(['message' => 'Review submitted successfully']);
            } else {
                return app(Response::class)->error(['message' => 'Review not submitted']);
            }
        } catch (Exception $e) {
            // Log the error
            return app(ActivityLogger::class)->logSystemActivity($e->getMessage(), ['data' => $inputData], 500, 'JSON');

            return app(Response::class)->error(['message' => $e->getMessage()]);
        }
    }
}
