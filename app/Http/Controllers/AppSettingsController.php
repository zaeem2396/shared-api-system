<?php

namespace App\Http\Controllers;

use App\Models\AppSettings;
use App\Utils\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppSettingsController extends Controller
{
    public function __construct(
        private Response $response,
        private AppSettings $appSettings
    ) {}

    public function createSettings(Request $request)
    {
        try {
            $inputData = $request->only('key', 'value', 'platformId');
            $validator = Validator::make($inputData, [
                'key' => 'required',
                'value' => 'required',
                'platformId' => 'required'
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $isSettingsCreated = $this->appSettings->createAppSettings($inputData);
            return $isSettingsCreated;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAppSettings(Request $request)
    {
        try {
            $inputData = $request->only('platformId');
            $validator = Validator::make($inputData, [
                'platformId' => 'nullable'
            ]);
            if ($validator->fails()) {
                return $this->response->error(['errors' => $validator->errors()->all()]);
            }
            $appSettings = $this->appSettings->fetchAppSettings($inputData);
            return $appSettings;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
