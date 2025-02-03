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

    public function getProduct() {
        try {
            /* Need to optomize this code */
            return $this->product->fetchProduct();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
