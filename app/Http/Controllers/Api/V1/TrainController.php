<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrainResource;
use App\Services\RealTimeTrains\Models\Train;
use App\Services\RealTimeTrains\RealTimeTrainsApiClient;
use Illuminate\Http\JsonResponse;

class TrainController extends Controller
{
    public function __construct(
        private RealTimeTrainsApiClient $client
    ) {
    }

    /**
     * Get detailed information about a specific train service
     *
     * @param string $serviceUid The service UID
     * @param string $year
     * @param string $month
     * @param string $day
     * @return JsonResponse
     */
    public function show(string $serviceUid, string $year, string $month, string $day): JsonResponse
    {
        $apiDate = sprintf('%s/%s/%s', $year, str_pad($month, 2, '0', STR_PAD_LEFT), str_pad($day, 2, '0', STR_PAD_LEFT));

        // Fetch detailed service from API
        $serviceData = $this->client->getServiceDetail($serviceUid, $apiDate);

        if (!$serviceData) {
            return response()->json(['error' => 'Train service not found'], 404);
        }

        $train = Train::fromApiResponse($serviceData);

        // Return as Train resource
        return response()->json(new TrainResource($train));
    }
}
