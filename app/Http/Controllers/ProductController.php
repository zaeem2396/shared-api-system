<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Utils\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProductController extends Controller
{
    public function __construct(
        private Response $response,
        private Product $product
    ) {}

    public function addProduct(Request $request)
    {
        try {
            $inputData = $request->only('vendorId', 'categoryId', 'name', 'description', 'price', 'stock', 'img');
            $token = $request->header('Authorization');
            if (!$token) {
                return $this->response->error(['error' => 'Unauthorized or token not provided']);
            }
            $validator = Validator::make($inputData, [
                'categoryId' => 'required',
                'subCategoryId' => 'required',
                'name' => 'required',
                'description' => 'required',
                'price' => 'required',
                'stock' => 'required',
                'img' => 'required'
            ]);
            $inputData['vendorId'] = JWTAuth::parseToken()->authenticate()->id;
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isProductCreated = $this->product->createProduct($inputData);
            return $isProductCreated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getProduct(Request $request)
    {
        try {
            $inputData = $request->only('skuId', 'q', 'categoryId', 'name', 'description', 'startingPrice', 'endingPrice', 'currentPage');
            $validator = Validator::make($inputData, [
                'skuId' => 'nullable',
                'q' => 'nullable',
                'categoryId' => 'nullable',
                'name' => 'nullable',
                'description' => 'nullable',
                'startingPrice' => 'nullable|numeric',
                'endingPrice' => 'nullable|numeric',
                'currentPage' => 'nullable|numeric',
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $products = $this->product->fetchProduct($inputData);
            return $products;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
