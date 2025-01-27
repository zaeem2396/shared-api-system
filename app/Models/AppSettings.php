<?php

namespace App\Models;

use App\Utils\ActivityLogger;
use App\Utils\Response;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSettings extends Model
{
    use HasFactory;
    protected $table = 'app_settings';
    protected $fillable = [
        'key',
        'value',
        'platformId'
    ];

    public static function createAppSettings(array $inputData)
    {
        try {
            $isAppSettingsExist = self::where('key', $inputData['key'])->where('platformId', $inputData['platformId'])->first();
            if ($isAppSettingsExist) {
                return app(Response::class)->duplicate(['message' => 'App settings already exist']);
            }
            $isAppSettingsCreated = self::create($inputData);
            if ($isAppSettingsCreated) {
                return app(Response::class)->success(['message' => 'App settings created successfully']);
            }
        } catch (Exception $e) {
            app(ActivityLogger::class)->logSystemActivity($e->getMessage(), $inputData, 500, 'JSON');

            return $e->getMessage();
        }
    }
}
