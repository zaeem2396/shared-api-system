<?php

namespace App\Utils;

class Response
{
    public static function success($data)
    {
        return json_encode([
            'status' => 200,
            'ack' => 'success',
            'response' => $data
        ]);
    }

    public static function error($data)
    {
        return json_encode([
            'status' => 500,
            'ack' => 'error',
            'response' => $data
        ]);
    }

    public static function duplicate($data)
    {
        return json_encode([
            'status' => 409,
            'ack' => 'duplicate',
            'response' => $data
        ]);
    }
}
