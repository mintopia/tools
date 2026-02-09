<?php
namespace App\Services\RealTimeTrains\Models;

use App\Models\TrainStation;
use Carbon\CarbonImmutable;

class CallingAt
{
    public TrainStation $station;
    public CarbonImmutable $expected;
    public CarbonImmutable $scheduled;
    public ?string $platform = null;
}
