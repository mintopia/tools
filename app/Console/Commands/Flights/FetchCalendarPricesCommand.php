<?php

namespace App\Console\Commands\Flights;

use App\Models\FlightCalendar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use function Laravel\Prompts\progress;

class FetchCalendarPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'flights:fetch-calendar-prices {--id= : Specific calendar ID to update}{--queue : Queue the processing}';

    /**
     * The console command description.
     */
    protected $description = 'Fetch flight prices for calendars';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $calendarId = $this->option('id');

        if ($calendarId) {
            $calendar = FlightCalendar::where('id', $calendarId)
                ->where('is_active', true)
                ->first();

            if (!$calendar) {
                $this->error("Calendar ID {$calendarId} not found or not active.");
                return Command::FAILURE;
            }

            $this->info("Fetching prices for: {$calendar->name}");
            $this->fetchPricesForCalendar($calendar);
        } else {
            $calendars = FlightCalendar::active()->get();

            if ($calendars->isEmpty()) {
                $this->warn('No active calendars found to update.');
                return Command::FAILURE;
            }

            foreach ($calendars as $calendar) {
                if ($this->option('queue')) {
                    $this->info("Queued fetching prices for: {$calendar->name}");
                    Artisan::queue('flights:fetch-calendar-prices', ['id' => $calendar->id]);
                } else {
                    $this->info("Fetching prices for: {$calendar->name}");
                    $this->fetchPricesForCalendar($calendar);
                }
            }
        }

        return Command::SUCCESS;
    }

    protected function fetchPricesForCalendar(FlightCalendar $calendar): void
    {
        $totalDays = $calendar->days_ahead;

        $progress = progress(
            label: "Fetching {$calendar->name}",
            steps: $totalDays
        );

        $progress->start();

        $totalFlights = $calendar->fetchPrices(function ($date, $current, $total) use ($progress) {
            $progress->label("Fetching {$date->format('D, M j, Y')}");
            $progress->advance();
        });

        $progress->finish();

        $this->info("Completed fetching prices for {$calendar->name} - Found {$totalFlights} flights");
        $this->newLine();
    }
}



