<?php

namespace App\Jobs;

use App\Services\ShopeeOrderSyncService;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessShopeeOrderUpdate
{
    use Dispatchable;

    public function __construct(
        public readonly string $orderSn,
        public readonly ?string $status = null,
    ) {}

    public function handle(ShopeeOrderSyncService $sync): void
    {
        $sync->syncOrder($this->orderSn, $this->status);
    }
}
