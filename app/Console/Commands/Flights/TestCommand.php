<?php

namespace App\Console\Commands\Flights;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Mintopia\LaravelFlights\FlightService;
use Mintopia\LaravelFlights\Models\Itinerary;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Laravel\Prompts\table;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flights:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test for flights';

    protected FlightService $flightService;

    const DATE_FORMAT = 'D d-M-Y H:i';
    protected SymfonyStyle $io;

    /**
     * Execute the console command.
     */
    public function handle(FlightService $flightService)
    {
        $this->flightService = $flightService;
        $this->io = new SymfonyStyle($this->input, $this->output);

        $query = $flightService->query();
        $query = $query->addSegment('LON', 'FAO', '+1 day');
        $itineraries = $query->get();
        $this->renderItineraries($itineraries);
        return self::SUCCESS;
    }


    /**
     * @param Collection<Itinerary> $itineraries
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
            'Class',
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
                    get_class($flight->departure),
                ];
                $price = '';
            }
        }

        $this->renderTable($headers, $rows);
    }

    protected function formatPrice(int $price, string $currency): string
    {
        return Number::currency($price / 100, in: $currency);
    }

    protected function renderTable(array $header, array $rows): void
    {
        if ($this->input->getOption('no-ansi')) {
            $this->io->table($header, $rows);
        } else {
            table($header, $rows);
        }
    }
}
