<?php

namespace App\Jobs;

use App\Models\CatalogProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class InsertBatchProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productsData;

    public function __construct(array $productsData)
    {
        $this->productsData = $productsData;
    }

    public function handle()
    {
        DB::beginTransaction();
        
        try {
            foreach ($this->productsData as $productData) {
                CatalogProduct::create($productData);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // TODO: Handle exception
            throw $e;
        }
    }
}