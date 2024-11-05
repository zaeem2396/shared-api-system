<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Utils\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogController extends Controller
{

    protected $blog;
    protected $response;

    public function __construct(BlogCategory $blog, Response $response)
    {
        $this->blog = $blog;
        $this->response = $response;
    }
    public function create(Request $request)
    {
        try {
            $inputData = $request->only('name');
            $validator = Validator::make($inputData, [
                'name' => 'required|max:255'
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isCategoryCreated = $this->blog->createCategory($inputData);
            return $isCategoryCreated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
