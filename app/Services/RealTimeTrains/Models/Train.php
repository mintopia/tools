<?php
namespace App\Services\RealTimeTrains\Models;

use App\Models\TrainStation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class Train
{
    public string $headCode;
    public string $operator;
    public ?CallingAt $from = null;
    public ?CallingAt $to = null;
    public ?CallingAt $origin = null;
    public ?CallingAt $destination = null;
    public ?Collection $callingAt = null;
    public ?string $serviceUid = null;

    public static function fromApiResponse(array $service): self
    {
        $train = new self();
        $train->headCode = $service['runningIdentity'] ?? 'Unknown';
        $train->operator = $service['atocName'] ?? 'Unknown';
        $train->serviceUid = $service['serviceUid'] ?? null;

        $callingAtPoints = collect();
        $previousTime = null;

        foreach ($service['locations'] ?? [] as $location) {
            $stop = self::mapCallingAt($location, $previousTime);
            if ($stop) {
                $callingAtPoints->push($stop);
                $previousTime = $stop->expected;
            }
        }

        if ($callingAtPoints->isNotEmpty()) {
            $train->callingAt = $callingAtPoints;
            $train->from = $callingAtPoints->first();
            $train->to = $callingAtPoints->last();
            $train->origin = $train->from;
            $train->destination = $train->to;
        }

        return $train;
    }

    private static function mapCallingAt(array $location, ?CarbonImmutable $previousTime = null): ?CallingAt
    {
        $crs = $location['crs'] ?? null;
        if (!$crs) {
            return null;
        }

        $station = TrainStation::where('crs', $crs)->first();
        if (!$station) {
            return null;
        }

        $scheduledTime = $location['gbttBookedDeparture'] ?? $location['gbttBookedArrival'] ?? null;
        $expectedTime = $location['realtimeDeparture'] ?? $location['realtimeArrival'] ?? null;

        if (!$scheduledTime || !$expectedTime) {
            return null;
        }

        $callingAt = new CallingAt();
        $callingAt->station = $station;
        $callingAt->platform = $location['platform'] ?? null;
        $callingAt->scheduled = self::parseTime($scheduledTime, $previousTime);
        $callingAt->expected = self::parseTime($expectedTime, $previousTime);

        return $callingAt;
    }

    private static function parseTime(string $time, ?CarbonImmutable $referenceTime = null): CarbonImmutable
    {
        $hour = (int)substr($time, 0, 2);
        $minute = (int)substr($time, 2, 2);

        $baseTime = $referenceTime ?? CarbonImmutable::now();
        $parsedTime = $baseTime->setTime($hour, $minute, 0);

        // If reference time is provided and parsed time is significantly earlier (> 12 hours),
        // assume it's the next day (handles midnight rollover)
        if ($referenceTime && $parsedTime->timestamp < $referenceTime->timestamp - 43200) {
            $parsedTime = $parsedTime->addDay();
        }

        return $parsedTime;
    }
}
