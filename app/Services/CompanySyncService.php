<?php

namespace App\Services;

use App\Models\Company;
use App\Services\Idea\IdeaApiClient;
use Illuminate\Support\Facades\DB;

class CompanySyncService
{
    protected IdeaApiClient $apiClient;

    public function __construct(IdeaApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Sync companies from IDEA API to database
     *
     * @return array{created: int, updated: int}
     */
    public function sync(): array
    {
        $companies = $this->apiClient->fetchCompanies();

        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($companies as $index => $companyData) {
            try {
                // ID field'ını kontrol et
                if (!isset($companyData['id'])) {
                    \Illuminate\Support\Facades\Log::warning('Company Sync: Missing ID field', [
                        'index' => $index,
                        'item_keys' => array_keys($companyData),
                    ]);
                    $errors++;
                    continue;
                }

                // CustomerSyncService formatına göre mapping
                $company = Company::updateOrCreate(
                    ['external_id' => $companyData['id']],
                    [
                        'name'      => $companyData['name'] ?? 'Unknown',
                        'company'   => $companyData['company'] ?? null,
                        'email'     => $companyData['email'] ?? null,
                        'is_active' => ($companyData['status'] ?? 1) == 1,
                        'synced_at' => now(),
                    ]
                );

                if ($company->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Company Sync: Failed to save company', [
                    'index' => $index,
                    'item' => $companyData,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        \Illuminate\Support\Facades\Log::info('Company Sync Completed', [
            'synced_count' => $created + $updated,
            'created' => $created,
            'updated' => $updated,
            'error_count' => $errors,
            'total_received' => count($companies),
        ]);

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }
}
