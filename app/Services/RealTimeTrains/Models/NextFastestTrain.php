<?php

namespace App\Services\RealTimeTrains\Models;

use App\Models\TrainStation;
use Carbon\CarbonImmutable;

class NextFastestTrain
{
    public string $headCode;
    public string $operator;
    public ?string $serviceUid = null;
    public ?CallingAt $from = null;
    public ?CallingAt $to = null;
    public ?CallingAt $origin = null;
    public ?CallingAt $destination = null;
    public ?string $serviceDate = null;

    public static function fromSearchResponses(
        array $fromService,
        array $arrivalService,
        TrainStation $fromStation,
        TrainStation $toStation,
        string $serviceDate
    ): ?self {
        $serviceUid = $fromService['serviceUid'] ?? null;
        if (!$serviceUid) {
            return null;
        }

        $fromStop = self::mapLocationDetailToCallingAt($fromService['locationDetail'] ?? [], $fromStation);
        // Pass departure time as reference to handle midnight rollover for arrivals
        $toStop = self::mapLocationDetailToCallingAt(
            $arrivalService['locationDetail'] ?? [],
            $toStation,
            $fromStop?->expected
        );

        if (!$fromStop || !$toStop) {
            return null;
        }

        // Extract train's actual origin and destination
        $originDescription = $fromService['locationDetail']['origin'][0]['description'] ?? null;
        $destinationDescription = $fromService['locationDetail']['destination'][0]['description'] ?? null;

        $originStation = TrainStation::where('name', $originDescription)->first();
        $destinationStation = TrainStation::where('name', $destinationDescription)->first();

        $train = new self();
        $train->headCode = $fromService['trainIdentity'] ?? $fromService['runningIdentity'] ?? 'Unknown';
        $train->operator = $fromService['atocName'] ?? 'Unknown';
        $train->serviceUid = $serviceUid;
        $train->serviceDate = $serviceDate;
        $train->from = $fromStop;
        $train->to = $toStop;

        // Set origin and destination as CallingAt objects if we have the stations
        if ($originStation) {
            $originCallingAt = new CallingAt();
            $originCallingAt->station = $originStation;
            $originCallingAt->scheduled = $fromStop->scheduled; // Placeholder times
            $originCallingAt->expected = $fromStop->expected;
            $train->origin = $originCallingAt;
        }

        if ($destinationStation) {
            $destinationCallingAt = new CallingAt();
            $destinationCallingAt->station = $destinationStation;
            $destinationCallingAt->scheduled = $toStop->scheduled; // Placeholder times
            $destinationCallingAt->expected = $toStop->expected;
            $train->destination = $destinationCallingAt;
        }

        return $train;
    }

    private static function mapLocationDetailToCallingAt(array $detail, TrainStation $station, ?CarbonImmutable $referenceTime = null): ?CallingAt
    {
        $scheduled = $detail['gbttBookedDeparture'] ?? $detail['gbttBookedArrival'] ?? null;
        $expected = $detail['realtimeDeparture'] ?? $detail['realtimeArrival'] ?? null;

        if (!$scheduled || !$expected) {
            return null;
        }

        $callingAt = new CallingAt();
        $callingAt->station = $station;
        $callingAt->platform = $detail['platform'] ?? null;
        $callingAt->scheduled = self::parseTime($scheduled, $referenceTime);
        $callingAt->expected = self::parseTime($expected, $referenceTime);

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
