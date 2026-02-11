<?php

namespace App\Console\Commands\Setup;

use App\Models\TrainStation;
use Illuminate\Console\Command;
use function Laravel\Prompts\text;

class ImportTrainStationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:import-train-stations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import train stations from CORPUS JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = text('Stations CSV', default: 'resources/data/stations.csv');
        if (!file_exists(base_path($filename))) {
            $this->error("File not found: " . base_path($filename));
            return self::FAILURE;
        }

        $this->processFile($filename);
        return self::SUCCESS;
    }

    protected function processFile(string $filename)
    {
        $handle = fopen(base_path($filename), 'r');
        $stations = [];
        while ($row = fgetcsv($handle)) {
            $stations[$row[0]] = $row[1];
        }
        $existing = TrainStation::select(['crs', 'name'])->get();
        $existing = $existing->keyBy('crs');
        foreach ($stations as $crs => $name) {
            if (!$existing->has($crs)) {
                $station = new TrainStation();
                $station->crs = $crs;
                $station->name = $name;
                $station->save();
                $this->output->writeln("Added: $crs - $name");
                continue;
            }
            $existingStation = $existing->get($crs);
            if ($existingStation->name !== $name) {
                $existingStation->name = $name;
                $existingStation->save();
                $this->output->writeln("Updated: $crs - $name");
            }
        }
        foreach ($existing as $crs => $station) {
            if (!array_key_exists($crs, $stations)) {
                $station->delete();
                $this->output->writeln("Deleted: $crs - $station->name");
            }
        }
    }
}
