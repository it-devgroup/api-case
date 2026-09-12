<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireStaleOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:expire-stale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire pending orders past their stock-hold deadline and restock their items.';

    public function handle(): int
    {
        $expired = 0;

        Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('expires_at', '<', now())
            ->with('items')
            ->chunkById(100, function ($orders) use (&$expired) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order) {
                        foreach ($order->items as $item) {
                            if ($item->product_id !== null) {
                                Product::whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
                            }
                        }

                        $order->update(['status' => OrderStatus::Expired]);
                    });

                    $expired++;
                }
            });

        $this->info("Expired {$expired} stale order(s).");

        return self::SUCCESS;
    }
}
