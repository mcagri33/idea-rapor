<?php

namespace App\Services\Idea;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IdeaApiClient
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        // Mutabakat modülüyle aynı stilde MAIN_API_URL ve MAIN_API_KEY kullan
        // MAIN_API_URL = https://ideadocs.com.tr/api/reconciliation
        $this->baseUrl = env('MAIN_API_URL', 'https://ideadocs.com.tr/api/reconciliation');
        $this->apiKey = env('MAIN_API_KEY');
    }

    /**
     * Fetch companies from IDEA API
     *
     * @return array
     */
    public function fetchCompanies(): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('MAIN_API_KEY is not configured in .env file');
        }

        try {
            // Mutabakat modülüyle aynı format: baseUrl + /users endpoint
            // Örnek: https://ideadocs.com.tr/api/reconciliation/users
            $url = $this->baseUrl . '/users';
            
            Log::info('Company Sync: Starting', ['url' => $url]);

            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept'    => 'application/json',
            ])
                ->timeout(30)
                ->get($url, [
                    'role'     => 'Company',  // Mutabakat modülünde 'Customer' kullanılıyor
                    'per_page' => 2000,
                ]);

            if (!$response->successful()) {
                Log::error('IDEA API Error: API unreachable', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'url'    => $url,
                ]);
                throw new \Exception('Failed to fetch companies from IDEA API. Status: ' . $response->status());
            }

            $json = $response->json();
            $data = $json['data'] ?? [];
            
            // Mutabakat modülüyle aynı logging stili
            Log::info('Company Sync: API Response', [
                'status' => $response->status(),
                'json_keys' => array_keys($json),
                'data_count' => count($data),
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error('IDEA API Error: ' . $e->getMessage(), [
                'url' => $url ?? 'unknown',
            ]);

            throw new \Exception('Failed to fetch companies from IDEA API: ' . $e->getMessage());
        }
    }
}
