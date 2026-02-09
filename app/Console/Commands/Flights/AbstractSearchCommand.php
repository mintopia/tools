<?php
namespace App\Console\Commands\Flights;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Mintopia\LaravelFlights\FlightService;
use Mintopia\LaravelFlights\Models\Itinerary;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\table;

abstract class AbstractSearchCommand extends Command
{

    const DATE_FORMAT = 'D d-M-Y H:i';
    public string $currency = 'GBP';
    protected SymfonyStyle $io;

    public function configure()
    {
        $this->addArgument('from', InputArgument::REQUIRED, 'Departure Airports');
        $this->addArgument('to', InputArgument::REQUIRED, 'Arrival Airports');
        $this->addOption('start', 's', InputOption::VALUE_REQUIRED, 'Start Date', '+1 day');
        $this->addOption('end', 'e', InputOption::VALUE_REQUIRED, 'End Date', null);
        $this->addOption('currency', 'c', InputOption::VALUE_REQUIRED, 'Currency', 'GBP');
        $this->addOption('maxstops', 'm', InputOption::VALUE_REQUIRED, 'Maximum Number of Stops', 0);
        $this->addOption('duration', 'd', InputOption::VALUE_REQUIRED, 'Duration of the trip', null);
        $this->addOption('airlines', 'a', InputOption::VALUE_REQUIRED, 'List of airlines to use', null);
        $this->addOption('flights', 'f', InputOption::VALUE_REQUIRED, 'List of flights to use', null);
        $this->addOption('no-cache', null, InputOption::VALUE_NONE, "Don't use the catch to fetch results");
    }

    public function __construct(protected FlightService $flightService)
    {
        parent::__construct();
    }

    protected function useCache(): bool
    {
        return !$this->input->hasOption('no-cache') || !$this->input->getOption('no-cache');
    }

    /**
     * @return array<<int>, array<int, string>>
     */
    protected function getConfigTable(): array
    {
        return [];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->io = new SymfonyStyle($this->input, $this->output);

        $this->currency = $this->input->getOption('currency');

        $maxStops = (int)$this->input->getOption('maxstops');
        $startDate = new CarbonImmutable($this->input->getOption('start'));
        if ($this->input->getOption('end') === null) {
            $endDate = clone $startDate;
        } else {
            $endDate = new CarbonImmutable($this->input->getOption('end'));
        }

        $from = collect(explode(',', $this->input->getArgument('from')));
        $to = collect(explode(',', $this->input->getArgument('to')));
        $duration = $this->input->getOption('duration');
        $airlines = collect(explode(',', $this->input->getOption('airlines')))->filter();
        $flights = collect(explode(',', $this->input->getOption('flights')))->filter();

        $configTable = [
            ['From', $from->implode(',')],
            ['To', $to->implode(',')],
            ['Start Date', $startDate->format('D d-M-Y')],
            ['End Date', $endDate->format('D d-M-Y')],
            ['Max Stops', $maxStops],
            ['Duration', $duration !== null ? Str::plural('day', $duration, true) : 'One-Way'],
            ['Airlines', $airlines->isEmpty() ? 'Any' : $airlines->implode(', ')],
            ['Flights', $flights->isEmpty() ? 'Any' : $flights->implode(', ')],
        ];
        $configTable = array_merge($configTable, $this->getConfigTable());

        $this->renderTable([], $configTable);

        $days = $startDate->diffInDays($endDate, true) + 1;
        $itineraries = collect();
        $progress = null;
        if (!$this->input->getOption('no-ansi')) {
            $progress = progress('Searching for flights', $days);
            $progress->start();
        }
        $date = $startDate;
        while ($date->lessThanOrEqualTo($endDate)) {
            if ($progress !== null) {
                $progress->hint('Found ' . Str::plural('flight', $itineraries->count(), true));
            }
            $return = null;
            if ($duration !== null) {
                $return = $date->addDays((int)$duration);
            }
            $itineraries = $itineraries->concat($this->getItineraries($from, $to, $date, $maxStops, $return, $airlines));
            if ($progress !== null) {
                $progress->advance();
            }
            $date = $date->addDay();
        }
        if ($progress !== null) {
            $progress->finish();
        }

        $itineraries = $this->filterByFlight($itineraries);
        $itineraries = $this->filterItineraries($itineraries);
        $itineraries = $this->sortItineraries($itineraries);
        $this->renderItineraries($itineraries);

        return self::SUCCESS;
    }

    protected function sortItineraries(Collection $itineraries): Collection
    {
        return $itineraries->sortBy(function ($itinerary) {
            return $itinerary->price;
        });
    }

    protected function filterByFlight(Collection $itineraries): Collection
    {
        $flights = array_filter(explode(',', $this->input->getOption('flights')));
        $airlines = array_filter(explode(',', $this->input->getOption('airlines')));
        return $itineraries->filter(function (Itinerary $itinerary) use ($flights, $airlines) {
            $found = 0;
            foreach ($itinerary->flights as $flight) {
                if (in_array($flight->code, $flights)) {
                    $found++;
                }
                if (!empty($airlines) && !in_array($flight->airline->code, $airlines)) {
                    return false;
                }
            }
            if (empty($flights)) {
                return true;
            }
            return $found > 0;
        });
    }


    protected function getItineraries(Collection $from, Collection $to, \DateTimeInterface $date, int $maxStops = 0, ?\DateTimeInterface $return = null, ?Collection $airlines = null): Collection
    {
        $type = $return !== null ? 'return' : 'one-way';
        $prefix = "Fetching {$type} flights for {$from->implode(', ')} -> {$to->implode(', ')} on {$date->format('d-M-Y')}";
        $key = 'flights:single:' . md5(serialize(func_get_args()));
        if ($this->useCache() && Cache::has($key)) {
            Log::info("{$prefix} from cache");
            return Cache::get($key);
        }
        Log::info("{$prefix} from Google");

        $query = $this->flightService->query();
        $airlineCodes = [];
        if ($airlines !== null) {
            $airlineCodes = $airlines->toArray();
        }
        $query = $query->addSegment($from->toArray(), $to->toArray(), $date, $maxStops, $airlineCodes);
        if ($return !== null) {
            $query = $query->addSegment($to->toArray(), $from->toArray(), $return, $maxStops, $airlineCodes);
        }
        $query = $query->setCurrency($this->currency);
        $itineraries = $query->get();

        Cache::put($key, $itineraries, 86400);
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
     * @param Collection<int, Itinerary> $itineraries
     * @return void
     */
    protected function renderItineraries(Collection $itineraries): void
    {
        $headers = [
            'Departure',
            'From',
            'To',
            'Arrival',
            'Airline',
            'Flight',
            'Duration',
            'Price',
        ];
        $rows = [];
        foreach ($itineraries->values() as $i => $itinerary) {
            if ($i > 0) {
                $rows[] = new TableSeparator();
            }
            $price = $this->formatPrice($itinerary->price, $itinerary->currency);
            foreach ($itinerary->flights as $flight) {
                $rows[] = [
                    $flight->departure->format(self::DATE_FORMAT),
                    $flight->from->code,
                    $flight->to->code,
                    $flight->arrival->format(self::DATE_FORMAT),
                    $flight->airline->name,
                    $flight->code,
                    $flight->duration->format('%hh %im'),
                    $price,
                ];
                $price = '';
            }
        }

        $this->renderTable($headers, $rows);
    }

    protected function formatPrice(int $price): string
    {
        return Number::currency($price / 100, in: $this->currency);
    }


    /**
     * @param Collection<int, Itinerary> $itineraries
     * @return Collection<int, Itinerary>
     */
    protected function filterItineraries(Collection $itineraries): Collection
    {
        return $itineraries;
    }

    protected function isPublicHoliday(\DateTimeInterface $date): bool
    {
        $holidays = Cache::remember('ukpublicholidays', 86400, function() {
            $data = file_get_contents('https://www.gov.uk/bank-holidays.json');
            return json_decode($data);
        });
        // Sorry regions, be a real country
        $holidays = collect(collect($holidays)->get('england-and-wales')->events)->pluck('date');
        return $holidays->contains($date->format('Y-m-d'));
    }
}
