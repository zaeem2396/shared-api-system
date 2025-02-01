<?php

namespace App\Utils;

use App\Models\Vendors;

class StoreId
{
    public function generateStoreId()
    {
        do {
            $storeId = app('Helper')->fetchAppSettings()['vendorStorePrefix'] . '-' . rand(1000, 9999);
            $isStoreIdExist = Vendors::where('store_id', $storeId)->first();
        } while ($isStoreIdExist);
        return $storeId;
    }
}
