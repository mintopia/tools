<?php

namespace App\Livewire;

use App\Models\Airport;
use App\Services\FlightSearch\BasicSearch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mintopia\Flights\Enums\BookingClass;
use Mintopia\Flights\Enums\PassengerType;

class FlightSearch extends Component
{
    // Search parameters with URL binding
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url(as: 'outbound')]
    public string $outboundDate = '';

    #[Url(as: 'return')]
    public ?string $returnDate = null;

    #[Url(as: 'type')]
    public string $tripType = 'one-way';

    #[Url]
    public int $adults = 1;

    #[Url(as: 'class')]
    public string $bookingClass = 'economy';

    #[Url(as: 'stops')]
    public int $maxStops = 0;

    #[Url]
    public string $airlines = '';

    public array $results = [];
    public bool $searched = false;
    public ?string $error = null;

    public function mount()
    {
        if (empty($this->outboundDate)) {
            $this->outboundDate = now()->addDay()->format('Y-m-d');
        }

        // Trigger search if we have from/to parameters
        if (!empty($this->from) && !empty($this->to)) {
            $this->search();
        }
    }

    public function search()
    {

        $this->tripType = $this->returnDate ? 'return' : 'one-way';

        $this->validate([
            'from' => 'required|string|max:10',
            'to' => 'required|string|max:10',
            'outboundDate' => 'required|date|after_or_equal:today|before_or_equal:' . now()->addDays(300)->format('Y-m-d'),
            'returnDate' => 'nullable|date|after:outboundDate|before_or_equal:' . now()->addDays(300)->format('Y-m-d'),
            'tripType' => 'sometimes|in:one-way,return',
            'adults' => 'required|integer|min:1|max:9',
            'bookingClass' => 'required|string|in:economy,premium_economy,business,first',
            'maxStops' => 'required|integer|min:0|max:3',
            'airlines' => 'nullable|string',
        ]);

        try {
            $this->error = null;
            $this->searched = true;

            $basicSearch = app(BasicSearch::class);

            $basicSearch->from = strtoupper($this->from);
            $basicSearch->to = strtoupper($this->to);
            $basicSearch->outbound = CarbonImmutable::parse($this->outboundDate);

            if (!empty($this->returnDate)) {
                $basicSearch->inbound = CarbonImmutable::parse($this->returnDate);
            }

            $basicSearch->passengers = array_fill(0, $this->adults, PassengerType::Adult);

            $basicSearch->bookingClass = match ($this->bookingClass) {
                'economy' => BookingClass::Economy,
                'premium_economy' => BookingClass::PremiumEconomy,
                'business' => BookingClass::Business,
                'first' => BookingClass::First,
                default => BookingClass::Economy,
            };

            $basicSearch->maxStops = $this->maxStops;

            if (!empty($this->airlines)) {
                $basicSearch->airlines = array_map('trim', explode(',', $this->airlines));
            }

            $searchResults = $basicSearch->search();

            // IMPORTANT: Extract all data from the flight objects BEFORE enriching with distances
            // because Livewire will proxy the objects and make properties inaccessible
            $this->results = $searchResults->map(function($itinerary, $index) {
                // Immediately extract all flight data before any manipulation
                $flightsArray = [];

                // Access the flights property - it should be iterable
                foreach ($itinerary->flights as $flightIndex => $flight) {
                    $flightsArray[] = [
                        'from' => [
                            'code' => $flight->from->code,
                            'name' => $flight->from->name ?? '',
                        ],
                        'to' => [
                            'code' => $flight->to->code,
                            'name' => $flight->to->name ?? '',
                        ],
                        'airline' => [
                            'code' => $flight->airline->code ?? '',
                            'name' => $flight->airline->name ?? '',
                        ],
                        'code' => $flight->code ?? '',
                        'departure' => $this->serializeDateTime($flight->departure),
                        'arrival' => $this->serializeDateTime($flight->arrival),
                        'duration' => $this->serializeDuration($flight->duration),
                    ];
                }

                return [
                    'price' => $itinerary->price,
                    'currency' => $itinerary->currency ?? 'GBP',
                    'flights' => $flightsArray,
                    'totalDistance' => 0, // Will be filled in next step
                    'legDistances' => [],
                ];
            })->values()->all();

            // Now enrich with distances using the original collection
            $this->enrichResultsWithDistancesFromOriginal($searchResults);
        } catch (\Exception $e) {
            $this->error = 'An error occurred while searching for flights. Please try again.';
            $this->results = [];
            \Log::error('Flight search error: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    protected function normalizeItinerary(object $itinerary): array
    {
        // This method is no longer used but kept for compatibility
        return [];
    }

    protected function serializeDateTime($dateTime): string
    {
        if (is_string($dateTime)) {
            return $dateTime;
        }
        if ($dateTime instanceof \DateTimeInterface) {
            return $dateTime->format('Y-m-d\TH:i:s\Z');
        }
        if (is_object($dateTime) && isset($dateTime->date)) {
            return $dateTime->date;
        }
        return '';
    }

    protected function serializeDuration($duration): string
    {
        if (is_string($duration)) {
            return $duration;
        }
        if ($duration instanceof \DateInterval) {
            return sprintf('PT%dH%dM', $duration->h, $duration->i);
        }
        if (is_object($duration)) {
            $h = $duration->h ?? 0;
            $i = $duration->i ?? 0;
            return sprintf('PT%dH%dM', $h, $i);
        }
        return 'PT0H0M';
    }

    protected function enrichResultsWithDistancesFromOriginal(Collection $originalResults)
    {
        if (!$originalResults || $originalResults->isEmpty()) {
            return;
        }

        // Get all unique airport codes from results
        $airportCodes = collect();
        foreach ($originalResults as $itinerary) {
            foreach ($itinerary->flights as $flight) {
                $airportCodes->push($flight->from->code);
                $airportCodes->push($flight->to->code);
            }
        }

        // Fetch all airports at once
        $airports = Airport::whereIn('iata', $airportCodes->unique()->toArray())
            ->get()
            ->keyBy('iata');

        // Calculate distances for each itinerary and update the normalized results
        $originalResults->each(function($itinerary, $index) use ($airports) {
            $totalDistance = 0;
            $legDistances = [];

            foreach ($itinerary->flights as $flight) {
                $fromAirport = $airports->get($flight->from->code);
                $toAirport = $airports->get($flight->to->code);

                if ($fromAirport && $toAirport && $fromAirport->lat && $fromAirport->lon && $toAirport->lat && $toAirport->lon) {
                    $distance = $this->calculateDistance(
                        $fromAirport->lat,
                        $fromAirport->lon,
                        $toAirport->lat,
                        $toAirport->lon
                    );
                    $legDistances[] = $distance;
                    $totalDistance += $distance;
                } else {
                    $legDistances[] = 0;
                }
            }

            // Update the normalized results array
            if (isset($this->results[$index])) {
                $this->results[$index]['totalDistance'] = $totalDistance;
                $this->results[$index]['legDistances'] = $legDistances;
            }
        });
    }

    protected function enrichResultsWithDistances(Collection $results)
    {
        if (!$results || $results->isEmpty()) {
            return;
        }

        // Get all unique airport codes from results
        $airportCodes = collect();
        foreach ($results as $itinerary) {
            foreach ($itinerary->flights as $flight) {
                $airportCodes->push($flight->from->code);
                $airportCodes->push($flight->to->code);
            }
        }

        // Fetch all airports at once
        $airports = Airport::whereIn('iata', $airportCodes->unique()->toArray())
            ->get()
            ->keyBy('iata');

        // Calculate distances for each itinerary
        foreach ($results as $itinerary) {
            $totalDistance = 0;
            $legDistances = [];

            foreach ($itinerary->flights as $flight) {
                $fromAirport = $airports->get($flight->from->code);
                $toAirport = $airports->get($flight->to->code);

                if ($fromAirport && $toAirport && $fromAirport->lat && $fromAirport->lon && $toAirport->lat && $toAirport->lon) {
                    $distance = $this->calculateDistance(
                        $fromAirport->lat,
                        $fromAirport->lon,
                        $toAirport->lat,
                        $toAirport->lon
                    );
                    $legDistances[] = $distance;
                    $totalDistance += $distance;
                } else {
                    $legDistances[] = 0;
                }
            }

            // Store distances on the itinerary object
            $itinerary->totalDistance = $totalDistance;
            $itinerary->legDistances = $legDistances;
        }
    }

    /**
     * Calculate distance between two points using Haversine formula
     * Returns distance in miles, rounded to nearest mile
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadiusMiles = 3959;

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) * sin($dlat / 2) +
             cos($lat1) * cos($lat2) *
             sin($dlon / 2) * sin($dlon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadiusMiles * $c;

        return (int) round($distance);
    }

    public function render()
    {
        return view('livewire.flight-search');
    }
}

