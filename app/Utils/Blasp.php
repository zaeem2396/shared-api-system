<?php

namespace App\Utils;

use Blaspsoft\Blasp\Facades\Blasp as FacadesBlasp;

class Blasp
{
    public function blaspHelper($para)
    {
        $check = FacadesBlasp::check($para);
        return $check->getCleanString();
    }
}
