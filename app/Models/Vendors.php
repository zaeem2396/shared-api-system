<?php

namespace App\Models;

use App\Utils\ActivityLogger;
use App\Utils\ImageKit;
use App\Utils\Intervention;
use App\Utils\MailService;
use App\Utils\Response;
use App\Utils\StoreId;
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
        'store_id',
        'storeName',
        'storeDescription',
        'logo',
        'publicId',
        'status'
    ];

    public static function addStore(array $inputData)
    {
        try {
            return app(Intervention::class)->resizeImageAndUploadToImageKit($inputData['logo']);
            /* Check if vendor is registered on platformId 11 */
            $isVendor = User::where('id', $inputData['userId'])->first();
            if ($isVendor->platform_id != app('Helper')->fetchAppSettings()['vendorPlatformId']) {
                app(ActivityLogger::class)->logSystemActivity('Vendor is not registered on platformId 11', ['userId' => $inputData['userId']], 409);
                app(ActivityLogger::class)->logUserActivity('Vendor is not registered on platformId 11', ['userId' => $inputData['userId']]);

                return app(Response::class)->error(['message' => 'System error occured']);
            }
            /* Check if vendor has more than 3 stores */
            $totalVendorStore = self::where('userId', $inputData['userId'])->count();
            if ($totalVendorStore >= app('Helper')->fetchAppSettings()['vendorStoreLimit']) {
                app(ActivityLogger::class)->logSystemActivity('Vendor has reached maximum store limit', ['userId' => $inputData['userId']], 409);
                app(ActivityLogger::class)->logUserActivity('Vendor has reached maximum store limit', ['userId' => $inputData['userId']]);

                return app(Response::class)->error(['message' => 'You have reached maximum store limit']);
            }
            /* Check if image is a valid image */
            $allowedExt = ['jpg', 'png', 'jpeg'];
            if (!in_array($inputData['logo']->getClientOriginalExtension(), $allowedExt)) {
                app(ActivityLogger::class)->logSystemActivity('Invalid image uploaded, process terminates', $inputData, 500, '');
                return app(Response::class)->error(['response' => 'Invalid image type, please upload only ' . implode(',', $allowedExt)]);
            }

            /* Upload image to imagekit */
            $inputData['store_id'] = app(StoreId::class)->generateStoreId();
            $imgKitRes = app(ImageKit::class)->uploadToImgKit($inputData['logo']);
            $inputData['logo'] = $imgKitRes['url'];
            $inputData['publicId'] = $imgKitRes['public_id'];
            $inputData['status'] = 'active';
            $isStoreCreated = self::create($inputData);
            if ($isStoreCreated) {
                /* Fetch register store email template */
                $emailTemplate = app(EmailTemplates::class)->where('name', 'store_registration')->first();
                if (!$emailTemplate) {
                    app(ActivityLogger::class)->logSystemActivity('Email template not found', ['name' => 'register_author'], 404);
                    app(ActivityLogger::class)->logUserActivity('Email template not found', ['name' => 'register_author'], 404);
                    return app(Response::class)->error(['message' => 'Processing failed due to technical fault']);
                }

                /* Prepare email body */
                $subject = $emailTemplate->subject;
                $content = strtr($emailTemplate->content, [
                    '[storeName]' => $inputData['storeName'],
                    '[storeDescription]' => $inputData['storeDescription'],
                    '[logo]' => $inputData['logo'],
                    '[store_id]' => $inputData['store_id']
                ]);

                /* Send email */
                app(MailService::class)->sendMail(
                    'no_reply@vendora.com',
                    $isVendor->email,
                    $subject,
                    $content
                );

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

            $getVendorDetails = User::select('id', 'name', 'email', 'role')->where('id', $inputData['userId'])->first();
            $getVendorStoreDetails = self::select('userId', 'storeName', 'storeDescription', 'logo', 'status')->where('userId', $inputData['userId'])->get();

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

    public static function updateStore(array $inputData)
    {
        try {
            $isStoreExist = self::where('userId', $inputData['userId'])->first();
            if (!$isStoreExist) {
                app(ActivityLogger::class)->logSystemActivity('Store does not exist', ['userId' => $inputData['userId']], 409);
                app(ActivityLogger::class)->logUserActivity('Store does not exist', ['userId' => $inputData['userId']]);

                return app(Response::class)->duplicate(['message' => 'Store does not exist']);
            }

            /* Check if image is a valid image */
            $allowedExt = ['jpg', 'png', 'jpeg'];
            if (!in_array($inputData['logo']->getClientOriginalExtension(), $allowedExt)) {
                app(ActivityLogger::class)->logSystemActivity('Invalid image uploaded, process terminates', $inputData, 500, '');
                return app(Response::class)->error(['response' => 'Invalid image type, please upload only ' . implode(',', $allowedExt)]);
            }
            if (isset($inputData['logo'])) {
                /* Delete old image from ImageKit */
                $isImgDeleted = app(ImageKit::class)->deleteFromImgKit($isStoreExist->publicId);
                if (!$isImgDeleted) {
                    app(ActivityLogger::class)->logSystemActivity('Failed to delete old image from ImageKit', $inputData, 500, 'JSON');
                    return app(Response::class)->error(['message' => 'Failed to delete old image']);
                }
                /* Upload image to imagekit */
                $imgKitRes = app(ImageKit::class)->uploadToImgKit($inputData['logo']);
                $inputData['logo'] = $imgKitRes['url'];
                $inputData['publicId'] = $imgKitRes['public_id'];
            }
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
}
