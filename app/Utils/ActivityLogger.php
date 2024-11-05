<?php

namespace App\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public function logUserActivity($action, $user, $details = [])
    {
        $logData = [
            'action' => $action,
            'user_id' => $user,
            'details' => json_encode($details) || [],
            'timestamp' => Carbon::now()->format('d-m-Y, H:i:s'),
        ];
        Log::channel('user_activity')->info(json_encode($logData));
    }

    public function logSystemActivity($message, $data = [], $statusCode = '', $responseType = '')
    {
        $logData = [
            'message' => $message,
            'data' => json_encode($data),
            'status_code' => $statusCode,
            'response_type' => $responseType || 'json',
            'timestamp' => Carbon::now()->format('d-m-Y, H:i:s'),
        ];
        Log::channel('system_activity')->debug(json_encode($logData));
    }
}
