<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Utils\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function __construct(
        private Review $review,
        private Response $response
    ) {}

    public function createReview(Request $request)
    {
        try {
            $inputdata = $request->only('user_id', 'blog_id', 'rating', 'comment', 'sentimentalScore');

            $validator = Validator::make($inputdata, [
                'user_id' => 'required',
                'blog_id' => 'required',
                'rating' => 'required',
                'comment' => 'required|min:15'
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isReviewSubmitted = $this->review->create($inputdata);
            return $isReviewSubmitted;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
