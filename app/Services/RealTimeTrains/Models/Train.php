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
        foreach ($service['locations'] ?? [] as $location) {
            $stop = self::mapCallingAt($location);
            if ($stop) {
                $callingAtPoints->push($stop);
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

    private static function mapCallingAt(array $location): ?CallingAt
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
        $callingAt->scheduled = self::parseTime($scheduledTime);
        $callingAt->expected = self::parseTime($expectedTime);

        return $callingAt;
    }

    private static function parseTime(string $time): CarbonImmutable
    {
        $hour = substr($time, 0, 2);
        $minute = substr($time, 2, 2);

        return CarbonImmutable::today()->setTime((int)$hour, (int)$minute);
    }
}
