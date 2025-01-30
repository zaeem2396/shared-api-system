<?php

namespace App\Helper;

use App\Models\AppSettings;

class Helper {

    public static function fetchAppSettings() {
        $settings =  app(AppSettings::class)->getAppSettings();
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }
        return $settings['response']['data'];
    }
}




/* To call the above function use app('Helper)->fechAppSettings */