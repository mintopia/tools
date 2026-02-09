<?php
namespace App\Console\Commands\Flights;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Mintopia\Flights\Enums\SortOrder;
use Mintopia\LaravelFlights\FlightService;
use Mintopia\LaravelFlights\Models\Itinerary;
use Mintopia\LaravelFlights\ProxyHelper;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Laravel\Prompts\table;

class ConnectingCommand extends AbstractSearchCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flights:connecting';

    const DATE_FORMAT = 'D d-M-Y H:i';
    public string $currency = 'GBP';
    protected SymfonyStyle $io;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch return flights via connecting airports';

    public function __construct(protected FlightService $flightService, protected ProxyHelper $proxyHelper)
    {
        parent::__construct($flightService);
    }

    public function configure()
    {
        parent::configure();
        $this->addArgument('connections', InputArgument::OPTIONAL, 'The connecting airports', 'OPO,LIS,MAD');
     }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->io = new SymfonyStyle($this->input, $this->output);

        $connections = explode(',', $this->input->getArgument('connections'));
        $from = explode(',', $this->input->getArgument('from'));
        $to = explode(',', $this->input->getArgument('to'));

        $this->currency = $this->input->getOption('currency');
        $this->flightService->setDefaultCurrency($this->currency);

        $departure = new CarbonImmutable($this->input->getOption('start'));
        $end =  new CarbonImmutable($this->input->getOption('end') ?? $departure->addDays(8));

        $this->renderTable([], [
            ['From', implode(', ', $from)],
            ['To', implode(', ', $to)],
            ['Connections', implode(', ', $connections)],
            ['Start Date', $departure->format(self::DATE_FORMAT)],
            ['End Date', $end->format(self::DATE_FORMAT)],
        ]);

        $outbound = $this->getFlights($from, $to, $connections, $departure, $end);
        $inbound = $this->getFlights($to, $from, $connections, $departure, $end);
        $results = $this->findReturns($outbound, $inbound);

        $this->render($results);

        return self::SUCCESS;
    }

    /**
     * @param Collection<int, Itinerary> $outboundItineraries
     * @param Collection<int, Itinerary> $returnItineraries
     * @return Collection<int, array<int, Itinerary>>
     */
    protected function findReturns(Collection $outboundItineraries, Collection $returnItineraries): Collection
    {
        $results = collect();
        foreach ($outboundItineraries as $outboundItinerary) {
            $this->debug("Checking outbound flight {$outboundItinerary->departure->format(self::DATE_FORMAT)} {$outboundItinerary->from->code} to {$outboundItinerary->to->code}");
            $outboundDeparture = new CarbonImmutable($outboundItinerary->departure);
            // Let's only fly on Thursdays, Fridays and Saturdays

            if (!$this->isPublicHoliday($outboundDeparture) && !$outboundDeparture->isThursday() && !$outboundDeparture->isFriday() && !$outboundDeparture->isSaturday()) {
                $this->debug('<fg=red>  Is not a public holiday, Thursday, Friday or Saturday</>');
                continue;
            }
            $outbound = $outboundItinerary->arrival;
            foreach ($returnItineraries as $returnItinerary) {
                $this->debug("  Checking return flight {$returnItinerary->departure->format(self::DATE_FORMAT)} {$returnItinerary->from->code} to {$returnItinerary->to->code}");
                $return = $returnItinerary->flights[0]->departure;

                // We only want to fly back at the end of the weekend
                if (!$this->isPublicHoliday($return) && !$return->isSunday() && !$return->isMonday()) {
                    //$this->debug('<fg=red>    Is not a public holiday, Sunday or Monday</>');
                    //continue;
                }
                // Ignore anything that leaves before we arrive
                if ($return->lessThan($outbound)) {
                    $this->debug('<fg=red>    Leaves before we arrive</>');
                    continue;
                }

                // No more than 4 days after our departure
                if ($return->greaterThan($outbound->addDays(3))) {
                    $this->debug('<fg=red>    Leaves too late</>');
                    continue;
                }

                // We want to be there a while
                if ($outbound->diffInHours($return, true) < 12) {
                    $this->debug('<fg=red>    Is not in our destination for long enough</>');
                    continue;
                }
                $this->debug('<fg=green>    Viable journey found!</>');
                $result = (object)[
                    'outbound' => $outboundItinerary,
                    'return' => $returnItinerary,
                    'best' => $this->getBestItinerary($outboundItinerary, $returnItinerary),
                ];
                $results->push($result);
            }
        }

        $results = $results->sortBy(function ($result) {
            return $result->outbound->flights[0]->departure->getTimestamp();
        });

        return $results;
    }

    protected function getFlights(array $from, array $to, array $connections, CarbonImmutable $date, CarbonImmutable $end): Collection
    {
        $results = collect();

        $firstleg = collect();
        $secondleg = collect();

        while ($date < $end) {
            $this->debug("Checking {$date->format('d-M-Y')}");
            $firstleg = $firstleg->concat($this->getOneWayItineraries($from, $connections, $date));
            $secondleg = $secondleg->concat($this->getOneWayItineraries($connections, $to, $date));
            $results = $results->concat($this->getOneWayItineraries($from, $to, $date));

            $date = $date->addDay();
        }

        $results = $results->concat($this->getViableJourneys($firstleg, $secondleg));

        $this->debug("Found {$results->count()} viable journeys");

        return $results->sortBy('outbound')->values();
    }

    protected function getViableJourneys(Collection $outboundItineraries, Collection $returnItineraries): Collection
    {
        $groupedReturns = $returnItineraries->groupBy(function (Itinerary $item, $key) {
            return $item->outbound->from->code;
        });
        $results = collect();
        foreach ($outboundItineraries as $outboundItinerary) {
            $this->debug("Checking {$outboundItinerary->departure->format(self::DATE_FORMAT)} {$outboundItinerary->from->code} to {$outboundItinerary->to->code}");
            $departure = $outboundItinerary->outbound->departure;
            $arrival = $outboundItinerary->outbound->arrival;
            $departureHour = $departure->hour;
            $tooEarly = 5;

            if ($this->isPublicHoliday($departure) || $departure->isWeekend()) {
                $tooEarly = 5;
            }

            if ($departureHour < $tooEarly) {
                $this->debug('<fg=red>  Flight is too early</>');
                continue;
            }
            /**
             * @var Itinerary $fao
             */
            $cutoff = $outboundItinerary->outbound->arrival->addHour();
            $possible = $groupedReturns->get($outboundItinerary->outbound->flights[0]->to->code, collect());
            foreach ($possible as $onward) {
                $this->debug("  Checking onward journey {$onward->departure->format(self::DATE_FORMAT)} {$onward->from->code} to {$onward->to->code}");
                /**
                 * @var Itinerary $onward
                 */
                if ($cutoff->greaterThan($onward->outbound->departure)) {
                    $this->debug('<fg=red>    Onward flight leaves too early</>');
                    continue;
                }

                $onwardDeparture = $onward->outbound->departure;
                $maxLayover = $arrival->isSameDay($onwardDeparture) ? 4 : 16;

                // No long layovers
                if ($arrival->diffInHours($onward->outbound->departure, true) > $maxLayover) {
                    $this->debug('<fg=red>    Layover is too long</>');
                    continue;
                }


                $this->debug('<fg=green>    Valid flight found</>');

                $itinerary = new \Mintopia\Flights\Models\Itinerary($this->flightService->proxiedObject);
                $itinerary->addJourney($outboundItinerary->proxiedObject->outbound);
                $itinerary->addJourney($onward->proxiedObject->outbound);
                $results->push($this->proxyHelper->translate($itinerary));
            }
        }
        $this->debug("  Found {$results->count()} viable journeys");
        return $results;
    }

    /**
     * @param string $from
     * @param \DateTimeInterface $departure
     * @param string $to
     * @param \DateTimeInterface $return
     * @return Collection<int, Itinerary>
     * @throws \DateMalformedStringException
     */
    protected function getReturnItineraries(string $from, \DateTimeInterface $departure, string $to, \DateTimeInterface $return, int $maxStops = 0): Collection
    {
        $results = $this->flightService
            ->query()
            ->addSegment($from, $to, $departure, $maxStops)
            ->addSegment($to, $from, $return, $maxStops)
            ->get();
        return $results;
    }

    protected function getOneWayItineraries(string|array $from, string|array $to, \DateTimeInterface $date): Collection
    {
        if (!is_array($from)) {
            $from = [$from];
        }
        if (!is_array($to)) {
            $to = [$to];
        }

        $toCodes = implode(', ', $to);
        $fromCodes = implode(', ', $from);
        $this->debug("Fetching flights from {$fromCodes} to {$toCodes}: ");

        $itineraries = $this->flightService
            ->query()
            ->addSegment($from, $to, $date)
            ->setSortOrder(SortOrder::Price)
            ->get();

        $this->debug("<fg=cyan>{$itineraries->count()}</>");

        return $itineraries;
    }

    protected function renderTable(array $header, array $rows): void
    {
        if ($this->input->getOption('no-ansi')) {
            $this->io->table($header, $rows);
        } else {
            table($header, $rows);
        }
    }

    /**
     * @param Collection<int, object{Itinerary, Itinerary, Itinerary[]}> $results
     * @return void
     */
    protected function render(Collection $results): void
    {
        $results = $results->sortBy(function ($result) {
            return $result->best->reduce(function ($sum, $item) {
                return $sum + $item->price;
            }, 0);
        });
        $number = 1;
        foreach ($results as $result) {

            $headers = [
                'Departure',
                'From',
                'To',
                'Arrival',
                'Airline',
                'Flight',
                'Layover',
                'Duration',
                'Price',
            ];

            $this->io->writeln(" <fg=green>Option {$number}</>", );
            $number++;

            $arrival = new CarbonImmutable($result->outbound->flights[1]->arrival);
            $departure = new CarbonImmutable($result->return->flights[0]->departure);
            $duration = $departure->diff($arrival, true)->format('%dd %hh %im');

            $bestPrice = $result->best->reduce(function ($price, $itinerary) {
                return $price + $itinerary->price;
            }, 0);
            $price = $result->outbound->price + $result->return->price;

            $routes = [];
            foreach ([$result->outbound, $result->return] as $itinerary) {
                $route = [$itinerary->flights[0]->from->code];
                foreach ($itinerary->flights as $flight) {
                    $route[] = $flight->to->code;
                }
                $routes[] = implode(' -> ', $route);
            }

            $rows = [
                ["Arrive", $arrival->format(self::DATE_FORMAT)],
                ["Leave", $departure->format(self::DATE_FORMAT)],
                ["Time in {$result->return->flights[0]->from->code}", $duration],
                ['Outbound', $routes[0]],
                ['Return', $routes[1]],
                ['Max Price', $this->formatPrice($price)],
                ['Best Price', $this->formatPrice($bestPrice)],
            ];

            $this->renderTable([], $rows);

            $this->io->writeln(' <fg=cyan>Itinerary</>');

            $rows = [];
            foreach ([$result->outbound, $result->return] as $i => $itinerary) {
                $layover = new CarbonImmutable($itinerary->flights[1]->departure)
                    ->diff($itinerary->flights[0]->arrival)->format('%hh %im');
                $duration = new CarbonImmutable($itinerary->flights[1]->arrival)
                    ->diff($itinerary->flights[0]->departure)->format('%hh %im');

                if ($i > 0) {
                    $rows[] = new TableSeparator();
                }

                foreach ($itinerary->journeys as $journey) {
                    foreach ($journey->flights as $flight) {
                        $rows[] = [
                            $flight->departure->format(self::DATE_FORMAT),
                            $flight->from->code,
                            $flight->to->code,
                            $flight->arrival->format(self::DATE_FORMAT),
                            $flight->airline->name,
                            $flight->code,
                            $layover,
                            $duration,
                            $this->formatPrice($journey->price),
                        ];
                        $layover = '';
                        $duration = '';
                    }
                }
            }

            $this->renderTable($headers, $rows);

            $this->io->writeln(' <fg=cyan>Cheapest Itinerary</>');

            $headers = [
                'Departure',
                'From',
                'To',
                'Arrival',
                'Airline',
                'Flight',
                'Price',
            ];
            $rows = [];
            foreach ($result->best as $i => $itinerary) {
                if ($i > 0) {
                    $rows[] = new TableSeparator();
                }

                $price = $this->formatPrice($itinerary->price);

                foreach ($itinerary->flights as $flight) {
                    $rows[] = [
                        $flight->departure->format(self::DATE_FORMAT),
                        $flight->from->code,
                        $flight->to->code,
                        $flight->arrival->format(self::DATE_FORMAT),
                        $flight->airline->name,
                        $flight->code,
                        $price,
                    ];
                    $price = '';
                }
            }

            $this->renderTable($headers, $rows);
        }
    }

    protected function getBestItinerary(Itinerary $outbound, Itinerary $return): Collection
    {
        // Take our initial outbound/return pair and make a collection of itinerary from it
        $options = collect();
        $flights = collect();
        $separate = collect();
        foreach ($outbound->journeys->concat($return->journeys) as $journey) {
            $itinerary = new \Mintopia\Flights\Models\Itinerary($this->flightService->proxiedObject);
            $itinerary->addJourney($journey->proxiedObject);
            $flights->push((object)[
                'date' => new CarbonImmutable($journey->departure),
                'code' => $journey->flights[0]->code
            ]);
            $separate->push($itinerary);
        }
        $options->push($separate);

        $route = $flights->pluck('code')->implode(' > ');

        // OK, so first, let's see if we can just do it all in one trip
        $returns = $this->getReturnItineraries(
            $outbound->flights[0]->from->code,
            $outbound->flights[0]->departure,
            $outbound->flights[0]->to->code,
            $return->flights[0]->departure,
            1
        );

        foreach ($returns as $itinerary) {
            if ($this->doFlightsMatch($itinerary, $flights)) {
                $options->push(collect($itinerary));
                $this->output->writeln("Found matching return for {$route}");
            }
        }

        // OK, so maybe not, let's break it down to 2 returns
        // FAO -> LIS; LIS -> BIO
        $part1 = $this->getReturnItineraries(
            $outbound->outbound->from->code,
            $outbound->outbound->departure,
            $outbound->outbound->to->code,
            $return->return->departure
        );

        $validSegment1 = collect();
        foreach ($part1 as $itinerary) {
            if ($this->doFlightsMatch($itinerary, $flights, [0, 3])) {
                $validSegment1->push($itinerary);
                $options->push(collect([
                    $itinerary,
                    $separate->get(1),
                    $separate->get(2),
                ]));
            }
        }
        if ($validSegment1->isNotEmpty()) {
            $part2 = $this->getReturnItineraries(
                $outbound->return->from->code,
                $outbound->return->departure,
                $outbound->return->to->code,
                $return->return->departure
            );

            $validSegment2 = collect();
            foreach ($part2 as $itinerary) {
                if ($this->doFlightsMatch($itinerary, $flights, [1, 2])) {
                    $validSegment2->push($itinerary);
                    $options->push(collect([
                        $itinerary,
                        $separate[0],
                        $separate[3],
                    ]));
                }
            }
            if ($validSegment2->isNotEmpty()) {
                $options->push(collect([
                    $validSegment1->sortBy(function (Itinerary $itinerary) {
                        return $itinerary->price;
                    })->first(),
                ]));
            }
        }

        // Finally return our best option
        $best = $options->sortBy(function (Collection $itineraries) {
            return $itineraries->sum(function ($itinerary) {
                return $itinerary->price;
            });
        })->first();

        return $best;
    }

    protected function doFlightsMatch(Itinerary $itinerary, Collection $flightsToMatch, ?array $filterKeys = null): bool
    {
        if ($filterKeys !== null) {
            $flights = collect();
            foreach ($filterKeys as $filterKey) {
                $flights->push($flightsToMatch->get($filterKey));
            }
            $flightsToMatch = $flights;
        }
        foreach ($itinerary->flights as $index => $flight) {
            if ($flightsToMatch->get($index)->code ?? null !== $flight->code) {
                return false;
            }
            if (!$flightsToMatch->get($index)->date->equalTo($flight->departure)) {
                return false;
            }
        }

        return true;
    }

    protected function debug(string $message): void
    {
        if ($this->verbosity === OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $this->io->writeln($message);
        }
    }
}
