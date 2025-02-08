<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Utils\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function __construct(
        private Response $response,
        private Category $category
    ){}

    public function category(Request $request)
    {
        try {
            $inputData = $request->only('id', 'name', 'method');
            $validator = Validator::make($inputData, [
                'id' => 'nullable',
                'name' => 'nullable',
                'method' => 'in:GET,POST,PUT,DELETE|nullable'
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $isCategoryCreated = $this->category->categoryAction($inputData);
            return $isCategoryCreated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
