<?php

namespace App\Console\Commands\Flights;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Mintopia\LaravelFlights\FlightService;
use Mintopia\LaravelFlights\Models\Itinerary;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Laravel\Prompts\table;

class IberiaCommand extends Command
{
    protected Collection $memo;

    const array AIRLINES = [
        'IB',
        'I2',
        'YW',
    ];

    const array AIRPORTS = [
        'MAD',
        'FAO',
        'OPO',
        'LIS',
        'SVQ',
        'BJZ',
        'XRY',
        'AGP',
        'GRX',
        'LEI',
        'ALC',
        'VLC',
        'CDT',
        'IBZ',
        'PMI',
        'MAH',
        'BCN',
        'LEU',
        'TLS',
        'EAS',
        'BIO',
        'PNA',
        'RJL',
        'SDR',
        'OVD',
        'LCG',
        'SCQ',
        'VGO',
    ];

    const MIN_LAYOVER = 60;
    const MAX_SECTOR_PRICE = 5000;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flights:iberia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search for Iberia tier point routing';

    public function __construct(protected FlightService $flightService)
    {
        parent::__construct();
    }

    public function configure() {
        $this->addArgument('start', InputArgument::OPTIONAL, 'Starting Airport', 'MAD');
        $this->addArgument('date', InputArgument::OPTIONAL, 'Date', CarbonImmutable::now()->nextWeekendDay()->format('Y-m-d'));
        $this->addOption('maxsectorprice', 'm', InputOption::VALUE_OPTIONAL, 'Maximum price per Sector', self::MAX_SECTOR_PRICE);
        $this->addOption('minlayover', 'l', InputOption::VALUE_OPTIONAL, 'Minimum layover time in minutes', self::MIN_LAYOVER );
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->input->getArgument('date');
        $start = $this->input->getArgument('start');
        if ($start === 'any') {
            $routes = $this->getRoutesFromAnywhere($date);
        } else {
            $routes = $this->getRoutes($start, $date);
        }

        $routes = $this->filterRoutes($routes);

        foreach ($routes as $route) {
            $this->displayFlights(collect($route));
        }
    }

    protected function totalPrice(array $route): int
    {
        return array_reduce($route, function (int $total, Itinerary $itinerary) {
            return $total + $itinerary->price;
        }, 0);
    }

    protected function filterRoutes(Collection $routes) {
        return $routes->filter(function ($route) {
            return ($this->totalPrice($route) / count($route)) < $this->input->getOption('maxsectorprice');
        });
    }

    protected function getRoutesFromAnywhere(string $date): Collection
    {
        $routes = [];
        foreach (self::AIRPORTS as $start) {
            $routes = array_merge($routes, $this->getRoutes($start, $date)->toArray());
        }
        $grouped = collect($routes)->groupBy(function (array $route) {
            return count($route);
        });
        $routes = collect();
        foreach ($grouped as $sectors => $itineraries) {
            $best = collect($itineraries)->sortBy(function (array $route) {
                return array_reduce($route, function (int $total, Itinerary $itinerary) {
                    return $total + $itinerary->price;
                }, 0);
            })->first();
            if (count($best) === 0) {
                continue;
            }
            $routes->push($best);
        }
        return $routes;
    }

    protected function getRoutes(string $start, string $date): Collection
    {
        $allFlights = [];
        foreach (self::AIRPORTS as $from) {
            $workingSet = collect(self::AIRPORTS)->except($from);
            foreach ($workingSet->chunk(5) as $to) {
                $flights = $this->flightService
                    ->query()
                    ->addSegment($from, $to->toArray(), new CarbonImmutable($date)->startOfDay(), 0, self::AIRLINES)
                    ->get()
                    ->toArray();
                $allFlights = array_merge($allFlights, $flights);
            }
        }

        $grouped = collect($allFlights)->filter(function (Itinerary $itinerary) {
            return in_array($itinerary->flights[0]->airline->code, self::AIRLINES);
        })->groupBy(function (Itinerary $itinerary) {
            return $itinerary->from->code;
        });

        $this->memo = collect();
        $startDate = new CarbonImmutable($date);
        $routes = $this->getRoutesFrom($grouped, $start, $startDate, []);

        $grouped = collect($routes)->groupBy(function (array $route) {
            return count($route);
        })->sortKeys();

        $result = collect();
        foreach ($grouped as $count => $routes) {
            $best = $routes->sortBy(function (array $route) {
                return array_reduce($route, function(int $total, Itinerary $itinerary) {
                    return $total + $itinerary->price;
                }, 0);
            })->first();
            $result->push($best);
        }
        return $result;
    }

    /**
     * @param Collection<string, Collection<int, Itinerary>> $flights
     * @param string $from
     * @param CarbonImmutable $time
     * @param array<int, Itinerary> $currentRoute
     * @return array<int, Collection<Itinerary>>
     */
    protected function getRoutesFrom(Collection $flights, string $from, CarbonImmutable $time, array $currentRoute): array
    {
        $departure = $time->addMinutes((int)$this->input->getOption('minlayover'));
        /**
         * @var Collection<int, Itinerary> $outbound
         */
        $outbound = $flights->get($from, collect())->sortBy('departure')->filter(function (Itinerary $itinerary) use ($departure) {
            return $itinerary->departure->greaterThanOrEqualTo($departure);
        });

        if ($outbound->isEmpty()) {
            return [$currentRoute];
        }

        $routes = [];
        foreach ($outbound as $flight) {
            /**
             * @var Itinerary $flight
             */
            $code = $flight->flights[0]->code;
            if ($this->memo->has($code)) {
                $routesFromHere = $this->memo->get($code)->toArray();
            } else {
                $newRoute = $currentRoute;
                $newRoute[] = $flight;
                $routesFromHere = $this->getRoutesFrom($flights, $flight->to->code, $flight->arrival, $newRoute);
                $this->memo->put($code, collect($routesFromHere));
            }
            $routes = array_merge($routes, $routesFromHere);
        }
        return $routes;
    }

    /**
     * @param Collection<int, Itinerary> $flights
     * @return void
     */
    protected function displayFlights(Collection $flights) {
        if ($flights->isEmpty()) {
            return;
        }
        if ($flights->count() <= 3) {
            return;
        }
        $total = $flights->reduce(function (int $total, Itinerary $itinerary) {
            return $total + $itinerary->price;
        }, 0);
        if ($total / $flights->count() > 5000) {
            return;
        }
        table([], [
            ['Date', $flights[0]->departure->format('Y-m-d')],
            ['Sectors', count($flights)],
            ['Total Cost', Number::currency($total / 100, in: 'GBP')],
            ['Cost per Sector',  Number::currency(($total / count($flights)) / 100, in: 'GBP')],
        ]);

        $rows = [];
        foreach ($flights as $i => $itinerary) {
            $layover = '';
            if ($flights->has($i + 1)) {
                $diff = $itinerary->arrival->diff($flights[$i + 1]->departure);
                $layover = $diff->forHumans(short: true);
                if ($diff->lessThan('PT1H')) {
                    $layover = "<fg=yellow>{$layover}</>";
                }
            }
            $rows[] = [
                $itinerary->from->code,
                $itinerary->departure->format('H:i'),
                $itinerary->arrival->format('H:i'),
                $itinerary->to->code,
                $layover,
                $itinerary->flights[0]->operator,
                $itinerary->flights[0]->code,
                Number::currency($itinerary->price / 100, in: $itinerary->currency),
            ];
        }

        table([
            'From',
            'Departure',
            'Arrival',
            'To',
            'Transfer',
            'Operator',
            'Flight',
            'Price',
        ], $rows);
    }

    /**
     * @param Collection<Itinerary> $route
     * @return void
     */
    protected function displayRoute(Collection $route) {
        $this->displayFlights($route);
    }
}
