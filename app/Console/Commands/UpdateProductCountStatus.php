<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateProductCountStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $appSetting = app('Helper')->fetchAppSettings();

        if (!$appSetting) {
            Log::error('Product status update failed: App settings not found.');
            return;
        }

        $inStockQty = (int) $appSetting['inStockQty'];
        $outOfStockQty = (int) $appSetting['outOfStockQty'];
        $limitedStockQty = (int) $appSetting['limitedStockQty'];

        Product::where('stock', '>', $inStockQty)->update(['status' => $appSetting['inStockMsg']]);
        Product::where('stock', '<=', $outOfStockQty)->update(['status' => $appSetting['outOfStockMsg']]);
        Product::whereBetween('stock', [$outOfStockQty + 1, $limitedStockQty])->update(['status' => $appSetting['limitedMsg']]);

        Log::info('Product statuses updated successfully.');
    }
}
