<?php

namespace App\Services\Amazon;

use App\Models\AiGeneration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonSpApiService
{
    private string $clientId;
    private string $clientSecret;
    private string $refreshToken;
    private string $marketplaceId;
    private string $region;

    public function __construct()
    {
        $this->clientId     = config('services.amazon.sp_api.client_id', '');
        $this->clientSecret = config('services.amazon.sp_api.client_secret', '');
        $this->refreshToken = config('services.amazon.sp_api.refresh_token', '');
        $this->marketplaceId = config('services.amazon.sp_api.marketplace_id', 'ATVPDKIKX0DER');
        $this->region       = config('services.amazon.sp_api.region', 'us-east-1');
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->refreshToken);
    }

    /**
     * Get LWA (Login with Amazon) access token
     */
    public function getAccessToken(): string
    {
        $response = Http::post('https://api.amazon.com/auth/o2/token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get Amazon access token: ' . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Submit a listing to Amazon Seller Central
     */
    public function submitListing(AiGeneration $generation): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Amazon SP-API is not configured. Add credentials to .env file.');
        }

        $import = $generation->productImport;
        $bullets = $generation->generated_bullet_points ?? [];

        $accessToken = $this->getAccessToken();
        $sku = 'ALB-' . $import->asin . '-' . time();

        $listing = [
            'productType' => 'PRODUCT',
            'requirements' => 'LISTING',
            'attributes' => [
                'item_name' => [['value' => $generation->generated_title, 'language_tag' => 'en_US', 'marketplace_id' => $this->marketplaceId]],
                'brand' => [['value' => $generation->brand_name, 'marketplace_id' => $this->marketplaceId]],
                'manufacturer' => [['value' => $generation->manufacturer, 'marketplace_id' => $this->marketplaceId]],
                'product_description' => [['value' => strip_tags($generation->generated_description ?? ''), 'language_tag' => 'en_US', 'marketplace_id' => $this->marketplaceId]],
                'bullet_point' => array_map(fn($b) => ['value' => $b, 'language_tag' => 'en_US', 'marketplace_id' => $this->marketplaceId], array_slice($bullets, 0, 5)),
                'generic_keyword' => [['value' => $generation->generated_search_terms ?? '', 'marketplace_id' => $this->marketplaceId]],
            ],
        ];

        $sellerId = auth()->user()->amazon_seller_id;
        if (empty($sellerId)) {
            throw new \Exception('Amazon Seller ID not configured in your profile.');
        }

        $endpoint = "https://sellingpartnerapi-na.amazon.com/listings/2021-08-01/items/{$sellerId}/{$sku}";

        $response = Http::withHeaders([
            'x-amz-access-token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->put($endpoint, $listing);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception('Amazon SP-API error: ' . ($error['errors'][0]['message'] ?? $response->body()));
        }

        $generation->update([
            'is_published' => true,
            'published_at' => now(),
            'amazon_listing_id' => $sku,
        ]);

        Log::info("Listing published to Amazon: {$sku}", ['generation_id' => $generation->id]);

        return [
            'success' => true,
            'sku' => $sku,
            'status' => $response->json('status'),
        ];
    }

    /**
     * Get seller account info
     */
    public function getSellerInfo(): array
    {
        if (!$this->isConfigured()) {
            return ['configured' => false];
        }

        try {
            $token = $this->getAccessToken();
            $response = Http::withHeaders([
                'x-amz-access-token' => $token,
            ])->get('https://sellingpartnerapi-na.amazon.com/sellers/v1/marketplaceParticipations');

            return [
                'configured' => true,
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return ['configured' => true, 'error' => $e->getMessage()];
        }
    }
}
