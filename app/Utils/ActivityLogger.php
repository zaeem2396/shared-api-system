<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public function logUserActivity($user, $action, $details = [])
    {
        $logData = [
            'user_id' => $user,
            'action' => $action,
            'details' => json_encode($details),
            'timestamp' => now(),
        ];
        Log::channel('user_activity')->info(json_encode($logData));
    }

    public function logSystemActivity($statusCode, $responseType, $message, $data = [])
    {
        $logData = [
            'status_code' => $statusCode,
            'response_type' => $responseType,
            'message' => $message,
            'data' => json_encode($data),
            'timestamp' => now(),
        ];
        Log::channel('system_activity')->debug(json_encode($logData));
    }
}
