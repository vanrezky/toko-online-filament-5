<?php

namespace App\Services;

use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Integration\Services\IntegrationLogService;
use Illuminate\Support\Facades\Http;
use Throwable;

class ApicoidOngkirService
{
    protected $baseUrl;

    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('regional.apicoid.base_url');
        $this->apiKey = config('regional.apicoid.key');
    }

    public function getShippingCost(string $originVillageCode, string $destinationVillageCode, int $weight): array
    {
        $url = rtrim((string) $this->baseUrl, '/').'/expedition/shipping-cost';
        $headers = [
            'x-api-co-id' => $this->apiKey,
        ];
        $query = [
            'origin_village_code' => $originVillageCode,
            'destination_village_code' => $destinationVillageCode,
            'weight' => $weight,
        ];
        $startedAt = hrtime(true);
        $logger = app(IntegrationLogService::class);
        $integrationLog = $logger->start([
            'direction' => IntegrationLog::DIRECTION_OUTBOUND,
            'provider' => 'apicoid',
            'type' => IntegrationLog::TYPE_API,
            'method' => 'GET',
            'url' => $url,
            'endpoint' => '/expedition/shipping-cost',
            'request_headers' => $headers,
            'request_body' => $query,
        ]);

        try {
            $response = Http::withHeaders($headers)
                ->withQueryParameters($query)
                ->get($url);

            $logger->finish($integrationLog, [
                'status' => $response->failed()
                    ? IntegrationLog::STATUS_FAILED
                    : IntegrationLog::STATUS_SUCCESS,
                'status_code' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => $response->body(),
                'response_content_type' => $response->header('Content-Type'),
                'error_message' => $response->failed() ? "HTTP {$response->status()}" : null,
                'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            ]);

            return $response->json();
        } catch (Throwable $exception) {
            $logger->fail($integrationLog, $exception);

            throw $exception;
        }
    }
}
