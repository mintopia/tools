<?php

namespace App\Services\RealTimeTrains;

use App\Models\TrainStation;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class RealTimeTrainsApiClient
{
    private Client $client;

    public function __construct(string $username, string $password, string $baseUrl)
    {
        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/'),
            'auth' => [$username, $password],
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 10,
        ]);
    }

    public function searchServices(TrainStation $from, ?TrainStation $to = null): array
    {
        try {
            if ($to) {
                $endpoint = sprintf('json/search/%s/to/%s', $from->crs, $to->crs);
            } else {
                $endpoint = sprintf('json/search/%s', $from->crs);
            }
            Log::debug('RTT API Call: searchServices', ['endpoint' => $endpoint]);

            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody()->getContents(), true);

            Log::debug('RTT API Response: searchServices', ['servicesCount' => count($data['services'] ?? [])]);

            return $data['services'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('RTT searchServices error', ['from' => $from->crs, 'to' => $to->crs ?? 'Any', 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function getServiceDetail(string $serviceUid, string $date): ?array
    {
        try {
            $endpoint = sprintf('json/service/%s/%s', $serviceUid, $date);
            Log::debug('RTT API Call: getServiceDetail', ['endpoint' => $endpoint]);

            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody()->getContents(), true);

            Log::debug('RTT API Response: getServiceDetail', ['serviceUid' => $serviceUid]);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('RTT getServiceDetail error', ['serviceUid' => $serviceUid, 'date' => $date, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function searchArrivals(TrainStation $to, TrainStation $from): array
    {
        try {
            $endpoint = sprintf('json/search/%s/from/%s/arrivals', $to->crs, $from->crs);
            Log::debug('RTT API Call: searchArrivals', ['endpoint' => $endpoint]);

            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody()->getContents(), true);

            Log::debug('RTT API Response: searchArrivals', ['servicesCount' => count($data['services'] ?? [])]);

            return $data['services'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('RTT searchArrivals error', ['to' => $to->crs, 'from' => $from->crs, 'error' => $e->getMessage()]);
            return [];
        }
    }
}
