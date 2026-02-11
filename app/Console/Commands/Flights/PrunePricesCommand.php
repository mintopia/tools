<?php

namespace App\Console\Commands\Flights;

use App\Models\FlightCalendar;
use App\Models\FlightPrice;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\Table;

use function Laravel\Prompts\confirm;

class PrunePricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'flights:prune-prices
                            {--before= : Delete prices before this date (default: yesterday)}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Prune old flight prices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $beforeDate = $this->option('before')
            ? CarbonImmutable::parse($this->option('before'))
            : CarbonImmutable::yesterday();

        $force = $this->option('force');

        // Get count of records to delete
        $count = FlightPrice::where('date', '<', $beforeDate)->count();

        if ($count === 0) {
            $this->info('No price records to prune.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} price records before {$beforeDate->toDateString()}");

        // Get counts per calendar
        $calendarCounts = DB::table('flight_prices')
            ->select('flight_calendar_id', DB::raw('count(*) as count'))
            ->where('date', '<', $beforeDate)
            ->groupBy('flight_calendar_id')
            ->get();

        if ($calendarCounts->isNotEmpty()) {
            $table = new Table($this->output);
            $table->setHeaders(['Calendar', 'Records to Delete']);

            foreach ($calendarCounts as $row) {
                $calendar = FlightCalendar::find($row->flight_calendar_id);
                $table->addRow([
                    $calendar ? $calendar->name : "Unknown (ID: {$row->flight_calendar_id})",
                    $row->count,
                ]);
            }

            $table->render();
            $this->newLine();
        }

        if (!$force) {
            $confirmed = confirm(
                label: "Delete {$count} price records before {$beforeDate->toDateString()}?",
                default: false
            );

            if (!$confirmed) {
                $this->info('Pruning cancelled.');
                return Command::SUCCESS;
            }
        }

        // Delete the records using model method
        $deleted = FlightCalendar::prunePrices($beforeDate);

        $this->info("Successfully deleted {$deleted} price records.");

        return Command::SUCCESS;
    }
}


