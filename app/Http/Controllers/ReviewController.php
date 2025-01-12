<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Utils\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ReviewController extends Controller
{
    public function __construct(
        private Review $review,
        private Response $response
    ) {}

    public function createReview(Request $request)
    {
        try {
            $inputData = $request->only('user_id', 'blog_id', 'rating', 'comment', 'sentimentalScore');
            $token = $request->header('Authorization');
            if (!$token) {
                return $this->response->error(['error' => 'Unauthorized or token not provided']);
            }
            $inputData['user_id'] = JWTAuth::parseToken()->authenticate()->id;
            $validator = Validator::make($inputData, [
                'blog_id' => 'required',
                'rating' => 'required',
                'comment' => 'required|min:5'
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isReviewSubmitted = $this->review->submitReview($inputData);
            return $isReviewSubmitted;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getReview(Request $request)
    {
        try {
            $inputData = $request->only('user_id', 'blog_id');

            if ($request->header('Authorization')) {
                try {
                    $inputData['user_id'] = JWTAuth::parseToken()->authenticate()->id;
                } catch (Exception $e) {
                    return $this->response->error(['message' => 'Invalid or expired token'], 401);
                }
            } else {
                $inputData['user_id'] = null; // Set user_id to null if no token is provided
            }
            $validator = Validator::make($inputData, [
                'blog_id' => 'required'
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $reviews = $this->review->fetchReviews($inputData);
            return $reviews;
        } catch (Exception $e) {
            return $this->response->error(['message' => $e->getMessage()]);
        }
    }
}
