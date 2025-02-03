<?php

namespace App\Utils;

use App\Models\Product;
use Illuminate\Support\Str;

class SkuId
{
    public function generateSkuId()
    {
        do {
            $skuId = app(Str::class)->random(10);
            $isSkuIdExist =  Product::where('skuId', $skuId)->first();
        } while ($isSkuIdExist);
        return $skuId;
    }
}
