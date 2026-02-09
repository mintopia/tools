<?php
namespace App\Services\RealTimeTrains;

use App\Models\TrainStation;
use App\Services\RealTimeTrains\Models\NextFastestTrain;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RealTimeTrainsService
{
    public function __construct(
        private RealTimeTrainsApiClient $client
    ) {
    }

    public function nextFastestTrains(TrainStation $from, TrainStation $to): Collection
    {
        $now = CarbonImmutable::now();
        $twoHoursFromNow = $now->addHours(2);
        $serviceDate = $now->format('Y-m-d');

        Log::debug('Searching next fastest trains', [
            'from' => $from->crs,
            'to' => $to->crs,
            'window' => '2 hours',
        ]);

        $fromToServices = collect($this->client->searchServices($from, $to))
            ->filter(fn($service) => $this->isInTimeWindow($service, $now, $twoHoursFromNow))
            ->unique('serviceUid')
            ->values();

        if ($fromToServices->isEmpty()) {
            Log::debug('No services found in time window');
            return collect();
        }

        Log::debug('Found services in time window', ['count' => $fromToServices->count()]);

        $arrivalServices = collect($this->client->searchArrivals($to, $from))
            ->keyBy('serviceUid');

        Log::debug('Found arrival services at destination', ['count' => $arrivalServices->count()]);

        $matchedTrains = $fromToServices
            ->map(function ($fromService) use ($arrivalServices, $from, $to, $serviceDate) {
                $arrivalService = $arrivalServices->get($fromService['serviceUid'] ?? '');
                if (!$arrivalService) {
                    return null;
                }

                return NextFastestTrain::fromSearchResponses(
                    $fromService,
                    $arrivalService,
                    $from,
                    $to,
                    $serviceDate
                );
            })
            ->filter();

        return $matchedTrains->sort(function ($a, $b) {
            $arrivalComparison = $a->to->expected <=> $b->to->expected;
            if ($arrivalComparison === 0) {
                return $b->from->expected <=> $a->from->expected;
            }

            return $arrivalComparison;
        })->values();
    }

    /**
     * Check if a service departs within the time window
     */
    private function isInTimeWindow(array $service, CarbonImmutable $now, CarbonImmutable $twoHoursFromNow): bool
    {
        $departureTime = $this->extractDepartureTime($service);

        return $departureTime &&
               $departureTime->greaterThanOrEqualTo($now) &&
               $departureTime->lessThanOrEqualTo($twoHoursFromNow);
    }

    /**
     * Extract departure time from service data (HHmm format)
     */
    private function extractDepartureTime(array $service): ?CarbonImmutable
    {
        $time = $this->extractTimeFromLocation(
            $service['locationDetail'] ?? [],
            ['gbtt_ptd', 'gbttBookedDeparture', 'realtimeDeparture']
        );


        return $time ? $this->parseTime($time) : null;
    }

    /**
     * Extract time value from location data, trying keys in order
     */
    private function extractTimeFromLocation(array $location, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!empty($location[$key])) {
                return $location[$key];
            }
        }

        return null;
    }

    /**
     * Parse HHmm time format to CarbonImmutable
     */
    private function parseTime(string $time): CarbonImmutable
    {
        $hour = substr($time, 0, 2);
        $minute = substr($time, 2, 2);

        return CarbonImmutable::today()->setTime((int)$hour, (int)$minute);
    }
}

