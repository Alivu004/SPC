<?php

namespace App\Jobs;

use stdClass;
use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Http\Traits\ResponseTrait;
use Illuminate\Queue\SerializesModels;
use App\Http\Traits\ShopifyProductTrait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Services\ProductClassificationService;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

class ProductsUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ShopifyProductTrait, ResponseTrait;

    /**
     * Shop's myshopify domain
     *
     * @var ShopDomain|string
     */
    public $shopDomain;

    /**
     * The webhook data
     *
     * @var object
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param string   $shopDomain The shop's myshopify domain.
     * @param stdClass $data       The webhook data (JSON decoded).
     *
     * @return void
     */
    public function __construct($shopDomain, $data)
    {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(IShopQuery $shopQuery)
    {
        $this->shopDomain = ShopDomain::fromNative($this->shopDomain);
        $shop = $shopQuery->getByDomain($this->shopDomain);
        $user = User::where('name', $shop->name)->first();
        $payload = $this->data;

        // --- Existing product persistence (products table) ---
        $this->getProductRepository(app(ProductRepositoryInterface::class));

        if ($this->storeData($payload, $user)) {
            $this->logInfo("Product Update Job Successfull.");
        } else {
            $this->logInfo("Product Update Job Failed");
        }

        // --- Classification: upsert shopify_products + re-run rules ---
        try {
            // Convert stdClass/object payload to plain array for the service
            $payloadArray = json_decode(json_encode($payload), true);
            app(ProductClassificationService::class)
                ->classifyProductFromWebhook($user, $payloadArray);
        } catch (\Throwable $e) {
            Log::error('[ProductsUpdateJob] Classification failed: ' . $e->getMessage(), [
                'shop'       => $shop->name,
                'shopify_id' => $payload->id ?? null,
                'exception'  => $e,
            ]);
        }
    }
}
