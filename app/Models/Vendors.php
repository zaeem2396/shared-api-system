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

    public static function updateStore(array $inputData)
    {
        try {
            $isVendorExist = self::where('userId', $inputData['userId'])->first();
            /* Check if venror exist */
            if (!$isVendorExist) {
                app(ActivityLogger::class)->logSystemActivity('Vendor does not exist', ['userId' => $inputData['userId']], 409);
                app(ActivityLogger::class)->logUserActivity('Vendor does not exist', ['userId' => $inputData['userId']]);

                return app(Response::class)->duplicate(['message' => 'Vendor does not exist']);
            }
            /* Check if store name already exist */
            if ($inputData['storeName'] === $isVendorExist->storeName) {
                app(ActivityLogger::class)->logSystemActivity('Store name already exists', $inputData, 409);
                app(ActivityLogger::class)->logUserActivity('Store name already exists', $inputData);

                return app(Response::class)->duplicate(['message' => 'Store name already exists']);
            }
            /* Check if image is a valid image */
            $allowedExt = ['jpg', 'png', 'jpeg'];
            if (!in_array($inputData['logo']->getClientOriginalExtension(), $allowedExt)) {
                app(ActivityLogger::class)->logSystemActivity('Invalid image uploaded, process terminates', $inputData, 500, '');
                return app(Response::class)->error(['response' => 'Invalid image type, please upload only ' . implode(',', $allowedExt)]);
            }
            /* Check if image already exist */
            if ($isVendorExist->logo != null) {
                $inputData['logo'] = $isVendorExist->logo;
                $inputData['publicId'] = $isVendorExist->publicId;
            } else {
                /* Upload image to imagekit */
                $imgKitRes = app(ImageKit::class)->uploadToImgKit($inputData['logo']);
                $inputData['logo'] = $imgKitRes['url'];
                $inputData['publicId'] = $imgKitRes['public_id'];
            }
            $inputData['status'] = 'active';
            $isStoreUpdated = self::where('userId', $inputData['userId'])->update($inputData);
            if ($isStoreUpdated) {
                app(ActivityLogger::class)->logSystemActivity('Store updated successfully', $inputData, 200);
                app(ActivityLogger::class)->logUserActivity('Store updated successfully', $inputData);

                return app(Response::class)->success(['message' => 'Store updated successfully']);
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
