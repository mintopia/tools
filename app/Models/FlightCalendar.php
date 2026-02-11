<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mintopia\LaravelFlights\FlightService;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $from_airport
 * @property string $to_airport
 * @property array $airlines
 * @property int $max_stops
 * @property bool $is_active
 * @property int $days_ahead
 * @property \Illuminate\Support\Carbon|null $last_fetched_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FlightCalendar extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'from_airport',
        'to_airport',
        'airlines',
        'max_stops',
        'is_active',
        'days_ahead',
        'last_fetched_at',
    ];

    protected $casts = [
        'airlines' => 'array',
        'is_active' => 'boolean',
        'last_fetched_at' => 'datetime',
    ];

    /**
     * Get from airports as array.
     */
    public function getFromAirportsAttribute(): array
    {
        return array_map('trim', explode(',', $this->from_airport));
    }

    /**
     * Get to airports as array.
     */
    public function getToAirportsAttribute(): array
    {
        return array_map('trim', explode(',', $this->to_airport));
    }

    /**
     * Get the route key name for Laravel route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the route name attribute.
     */
    public function getRouteNameAttribute(): string
    {
        return "{$this->from_airport} to {$this->to_airport}";
    }

    /**
     * Get all flight prices for this calendar.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(FlightPrice::class);
    }

    /**
     * Get all history records for this calendar.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(FlightCalendarHistory::class);
    }

    /**
     * Scope a query to only include active calendars.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Fetch prices for this calendar.
     */
    public function fetchPrices(?callable $progressCallback = null): int
    {
        $flightService = app(FlightService::class);

        $startDate = CarbonImmutable::today();
        $endDate = $startDate->addDays($this->days_ahead - 1);

        $dates = [];
        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            $dates[] = $currentDate;
            $currentDate = $currentDate->addDay();
        }

        $totalFlights = 0;

        foreach ($dates as $index => $date) {
            if ($progressCallback) {
                $progressCallback($date, $index + 1, count($dates));
            }

            $flightsCount = $this->fetchPricesForDate($date, $flightService);
            $totalFlights += $flightsCount;
        }

        // Update last fetched timestamp
        $this->update(['last_fetched_at' => now()]);

        return $totalFlights;
    }

    /**
     * Fetch prices for a specific date.
     */
    protected function fetchPricesForDate(CarbonImmutable $date, FlightService $flightService): int
    {
        try {
            $fromAirports = $this->fromAirports;
            $toAirports = $this->toAirports;
            $allResults = collect();

            // Query each combination of from/to airports
            foreach ($fromAirports as $fromAirport) {
                foreach ($toAirports as $toAirport) {
                    $query = $flightService->query()
                        ->addSegment(
                            $fromAirport,
                            $toAirport,
                            $date,
                            $this->max_stops,
                            $this->airlines
                        );

                    $results = $query->get();
                    $allResults = $allResults->merge($results);
                }
            }

            $pricesForDate = collect();

            foreach ($allResults as $itinerary) {
                foreach ($itinerary->flights as $flight) {

                    // Handle price - can be either object with amount property or direct integer
                    $priceAmount = is_object($itinerary->price) ? $itinerary->price->amount : $itinerary->price;
                    $priceCurrency = is_object($itinerary->price) ? $itinerary->price->currency : 'GBP';

                    // Convert from pence to pounds
                    $priceGbp = $priceAmount / 100;

                    $flightPrice = FlightPrice::updateOrCreate(
                        [
                            'flight_calendar_id' => $this->id,
                            'date' => $date->toDateString(),
                            'flight_number' => $flight->airline->code . $flight->number,
                        ],
                        [
                            'departure_time' => $flight->departure->format('H:i:s'),
                            'arrival_time' => $flight->arrival->format('H:i:s'),
                            'price_gbp' => $priceGbp,
                            'currency' => $priceCurrency,
                            'raw_data' => [
                                'itinerary' => [
                                    'price' => $priceGbp,
                                    'currency' => $priceCurrency,
                                ],
                                'flight' => [
                                    'number' => $flight->airline->code . $flight->number,
                                    'airline' => [
                                        'code' => $flight->airline->code,
                                        'name' => $flight->airline->name,
                                    ],
                                    'from' => [
                                        'code' => $flight->from->code,
                                        'name' => $flight->from->name,
                                    ],
                                    'to' => [
                                        'code' => $flight->to->code,
                                        'name' => $flight->to->name,
                                    ],
                                    'departure' => $flight->departure->toIso8601String(),
                                    'arrival' => $flight->arrival->toIso8601String(),
                                    'duration' => $flight->duration->totalMinutes,
                                ],
                            ],
                        ]
                    );

                    $pricesForDate->push($flightPrice);
                }
            }

            // Calculate and store daily statistics
            if ($pricesForDate->isNotEmpty()) {
                FlightCalendarHistory::updateOrCreate(
                    [
                        'flight_calendar_id' => $this->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'highest_price_gbp' => $pricesForDate->max('price_gbp'),
                        'average_price_gbp' => $pricesForDate->avg('price_gbp'),
                        'flight_count' => $pricesForDate->count(),
                    ]
                );
            }

            return $pricesForDate->count();
        } catch (\Exception $e) {
            \Log::error("Error fetching prices for {$date->toDateString()}: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Fetch prices for all active calendars.
     */
    public static function fetchAllActivePrices(?callable $progressCallback = null): int
    {
        $calendars = static::active()->get();
        $totalFlights = 0;

        foreach ($calendars as $calendar) {
            $totalFlights += $calendar->fetchPrices($progressCallback);
        }

        return $totalFlights;
    }

    /**
     * Prune old prices before a specific date.
     */
    public static function prunePrices(CarbonImmutable $beforeDate): int
    {
        return FlightPrice::where('date', '<', $beforeDate)->delete();
    }
}





