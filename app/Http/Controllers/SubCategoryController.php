<?php

namespace App\Http\Controllers;

use App\Utils\Response;
use App\Models\SubCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubCategoryController extends Controller
{
    public function __construct(
        private Response $response,
        private SubCategory $subCategory
    ){}

    public function subCategory(Request $request)
    {
        try {
            $inputData = $request->only('id', 'name', 'categoryId', 'method');
            $validator = Validator::make($inputData, [
                'id' => 'nullable',
                'name' => 'nullable',
                'categoryId' => 'nullable',
                'method' => 'in:GET,POST,PUT,DELETE|required'
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $isSubCategoryCreated = $this->subCategory->SubCategoryAction($inputData);
            return $isSubCategoryCreated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
