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

    public function getCategory()
    {
        try {
            return $this->blog->all();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function update(Request $request)
    {
        try {
            $inputData = $request->only('id', 'name');
            $validator = Validator::make($inputData, [
                'id' => 'required',
                'name' => 'required|max:255'
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isCategoryUpdated = $this->blog->updateCategory($inputData);
            return $isCategoryUpdated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
