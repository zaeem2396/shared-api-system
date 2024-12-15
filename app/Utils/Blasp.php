<?php

namespace App\Utils;

use Blaspsoft\Blasp\Facades\Blasp as FacadesBlasp;

class Blasp
{
    public function blaspHelper($para): string
    {
        $check = FacadesBlasp::check($para);
        return $check->getCleanString();
    }
}
