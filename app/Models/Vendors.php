<?php

namespace App\Models;

use App\Utils\ActivityLogger;
use App\Utils\ImageKit;
use App\Utils\Response;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Vendors extends Model
{
    protected $table = 'vendors';

    protected $primaryKey = 'id';

    protected $fillable = [
        'userId',
        'storeName',
        'storeDescription',
        'logo',
        'publicId',
        'status'
    ];

    public static function addStore(array $inputData)
    {
        try {
            $isVendorExist = self::where('storeName', $inputData['storeName'])->first();
            /* Check if vendor store exist */
            if ($isVendorExist) {
                app(ActivityLogger::class)->logSystemActivity('Vendor already exist', ['userId' => $inputData['userId']], 409);
                app(ActivityLogger::class)->logUserActivity('Vendor already exist', ['userId' => $inputData['userId']]);

                return app(Response::class)->duplicate(['message' => 'Vendor already exist']);
            }
            /* Check if image is a valid image */
            $allowedExt = ['jpg', 'png', 'jpeg'];
            if (!in_array($inputData['logo']->getClientOriginalExtension(), $allowedExt)) {
                app(ActivityLogger::class)->logSystemActivity('Invalid image uploaded, process terminates', $inputData, 500, '');
                return app(Response::class)->error(['response' => 'Invalid image type, please upload only ' . implode(',', $allowedExt)]);
            }

            /* Upload image to imagekit */
            $imgKitRes = app(ImageKit::class)->uploadToImgKit($inputData['logo']);
            $inputData['logo'] = $imgKitRes['url'];
            $inputData['publicId'] = $imgKitRes['public_id'];
            $inputData['status'] = 'active';
            $isStoreCreated = self::create($inputData);
            if ($isStoreCreated) {
                app(ActivityLogger::class)->logSystemActivity('Store created successfully', $inputData, 200);
                app(ActivityLogger::class)->logUserActivity('Store created successfully', $inputData);

                return app(Response::class)->success(['message' => 'Store created successfully']);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), $inputData, 500, 'JSON');

            return $e->getMessage();
        }
    }

    public static function getVendorProfile(array $inputData)
    {
        try {
            $isVendorExist = self::where('userId', $inputData['userId'])->first();
            if (!$isVendorExist) {
                app(ActivityLogger::class)->logSystemActivity('Vendor does not exist', ['userId' => $inputData['userId']], 409);
                app(ActivityLogger::class)->logUserActivity('Vendor does not exist', ['userId' => $inputData['userId']]);

                return app(Response::class)->duplicate(['message' => 'Vendor does not exist']);
            }

            $getVendorDetails = User::select('id', 'name', 'email')->where('id', $inputData['userId'])->first();
            $getVendorStoreDetails = self::select('userId', 'storeName', 'storeDescription', 'logo', 'status')->where('userId', $inputData['userId'])->first();

            $getVendorProfile = [
                'vendorDetails' => $getVendorDetails,
                'storeDetails' => $getVendorStoreDetails
            ];
            if ($getVendorProfile) {
                app(ActivityLogger::class)->logSystemActivity('Vendor profile fetched successfully', $inputData, 200);
                app(ActivityLogger::class)->logUserActivity('Vendor profile fetched successfully', $inputData);

                return app(Response::class)->success(['data' => $getVendorProfile]);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), $inputData, 500, 'JSON');

            return $e->getMessage();
        }
    }
}
