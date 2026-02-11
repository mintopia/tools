<?php
declare(strict_types=1);

namespace App\Services\FlightSearch;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Mintopia\Flights\Enums\BookingClass;
use Mintopia\Flights\Enums\PassengerType;
use Mintopia\Flights\Enums\SortOrder;
use Mintopia\LaravelFlights\FlightService;
use Mintopia\LaravelFlights\QueryBuilder;

abstract class AbstractFlightSearch
{
    public array $passengers = [
        PassengerType::Adult,
    ];
    public CarbonImmutable $outbound;
    public ?CarbonImmutable $inbound = null;
    public string $from = 'LON';
    public string $to = 'FAO';
    public int $maxStops = 0;
    public array $airlines = [];
    public BookingClass $bookingClass = BookingClass::Economy;
    public SortOrder $sortOrder = SortOrder::Price;

    public function __construct(protected FlightService $flightService)
    {
        $this->outbound = CarbonImmutable::now();
    }

    public function search(): Collection
    {
        /**
         * @var QueryBuilder $query
         */
        $query = $this->flightService->query();
        $query = $query
            ->setPassengers($this->passengers)
            ->setBookingClass($this->bookingClass)
            ->setSortOrder($this->sortOrder)
            ->addSegment($this->from, $this->to, $this->outbound, $this->maxStops, $this->airlines);

        if ($this->inbound !== null) {
            $query = $query->addSegment($this->to, $this->from, $this->inbound, $this->maxStops, $this->airlines);
        }

        $results = $query->get();
        $results = $this->filterResults($results);
        $results = $this->sortResults($results);

        return $results;
    }

    protected function filterResults(Collection $results): Collection
    {
        return $results;
    }

    protected function sortResults(Collection $results): Collection
    {
        return $results;
    }
}
