<?php

namespace App\Console\Commands\Setup;

use App\Models\Airport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportAirportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:import-airports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import airports from GitHub JSON files';

    const AIRPORTS_URL = 'https://raw.githubusercontent.com/mintopia/iata-airports/refs/heads/master/airports.json';
    const METROPOLITAN_URL = 'https://raw.githubusercontent.com/mintopia/iata-airports/refs/heads/master/metropolitan-areas.json';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching airports data...');

        // Fetch airports
        $airportsResponse = Http::get(self::AIRPORTS_URL);
        if (!$airportsResponse->successful()) {
            $this->error('Failed to fetch airports data');
            return self::FAILURE;
        }

        // Fetch metropolitan areas
        $metropolitanResponse = Http::get(self::METROPOLITAN_URL);
        if (!$metropolitanResponse->successful()) {
            $this->error('Failed to fetch metropolitan areas data');
            return self::FAILURE;
        }

        $airportsData = $airportsResponse->json();
        $metropolitanData = $metropolitanResponse->json();

        $this->info('Processing airports...');
        $this->processAirports($airportsData);

        $this->info('Processing metropolitan areas...');
        $this->processMetropolitanAreas($metropolitanData);

        $this->info('Import completed successfully!');
        return self::SUCCESS;
    }

    protected function processAirports(array $data): void
    {
        collect($data)->chunk(100)->each(function ($airportsChunk) {
            $iataCodes = $airportsChunk->pluck('iata');
            $existing = Airport::whereIn('iata', $iataCodes)->get()->keyBy('iata');
            foreach ($airportsChunk as $airportData) {
                if (!$airportData['iata']) {
                    $this->output->writeln("Skipping airport with missing IATA code: {$airportData['name']}");
                    continue;
                }
                $iata = $airportData['iata'];
                if (!$existing->has($iata)) {
                    Airport::create((array)$airportData);
                    $this->output->writeln("Added: {$iata} - {$airportData['name']}");
                } else {
                    $existingAirport = $existing->get($iata);
                    $existingAirport->update((array)$airportData);
                    $this->output->writeln("Updated: {$iata} - {$airportData['name']}");
                }
            }
        });
    }


    protected function processMetropolitanAreas(array $data): void
    {
        $data = collect($data)->keyBy('iata');
        $existing = Airport::whereIn('iata', $data->keys())->get()->keyBy('iata');

        foreach ($data as $iata => $metroData) {
            $metroData['name'] = $metroData['city'] . ' (All Airports)';
            if (!$existing->has($iata)) {
                Airport::create($metroData);
                $this->output->writeln("Added: {$iata} - {$metroData['name']}");
            } else {
                $existingAirport = $existing->get($iata);
                $existingAirport->update($metroData);
                $this->output->writeln("Updated: {$iata} - {$metroData['name']}");
            }
        }
    }
}

