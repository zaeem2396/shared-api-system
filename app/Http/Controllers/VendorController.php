<?php

namespace App\Http\Controllers;

use App\Models\Vendors;
use App\Utils\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class VendorController extends Controller
{

    public function __construct(
        private Response $response,
        private Vendors $vendor
    ) {}

    public function updateVendorStore(Request $request)
    {
        try {
            $inputData = $request->only('userId', 'storeName', 'storeDescription', 'logo');
            $token = $request->header('Authorization');
            if (!$token) {
                return $this->response->error(['error' => 'Unauthorized or token not provided']);
            }
            $inputData['userId'] = JWTAuth::parseToken()->authenticate()->id;

            $validator = Validator::make($inputData, [
                'storeName' => 'nullable',
                'storeDescription' => 'nullable',
                'logo' => 'nullable'
            ]);

            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }

            $isStoreUpdated = $this->vendor->updateStore($inputData);
            return $isStoreUpdated;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
