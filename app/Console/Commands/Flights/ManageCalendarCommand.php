<?php

namespace App\Console\Commands\Flights;

use App\Models\Airport;
use App\Models\FlightCalendar;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\Table;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class ManageCalendarCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'flights:calendar-manage';

    /**
     * The console command description.
     */
    protected $description = 'Manage flight calendars';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        while (true) {
            $action = select(
                label: 'What would you like to do?',
                options: [
                    'list' => 'List all calendars',
                    'create' => 'Create new calendar',
                    'update' => 'Update existing calendar',
                    'delete' => 'Delete calendar',
                    'exit' => 'Exit',
                ]
            );

            match ($action) {
                'list' => $this->listCalendars(),
                'create' => $this->createCalendar(),
                'update' => $this->updateCalendar(),
                'delete' => $this->deleteCalendar(),
                'exit' => exit(0),
            };
        }
    }

    protected function listCalendars(): void
    {
        $calendars = FlightCalendar::all();

        if ($calendars->isEmpty()) {
            warning('No flight calendars found.');
            return;
        }

        $table = new Table($this->output);
        $table->setHeaders(['ID', 'Slug', 'Name', 'Route', 'Airlines', 'Max Stops', 'Active', 'Last Fetched']);

        foreach ($calendars as $calendar) {
            $table->addRow([
                $calendar->id,
                $calendar->slug,
                $calendar->name,
                "{$calendar->from_airport} → {$calendar->to_airport}",
                implode(', ', $calendar->airlines),
                $calendar->max_stops,
                $calendar->is_active ? 'Yes' : 'No',
                $calendar->last_fetched_at?->diffForHumans() ?? 'Never',
            ]);
        }

        $table->render();
        $this->newLine();
    }

    protected function createCalendar(): void
    {
        $slug = text(
            label: 'Slug (leave empty to auto-generate)',
            placeholder: 'e.g., stn-fao',
            required: false
        );

        $name = text(
            label: 'Calendar name',
            placeholder: 'e.g., STN to FAO',
            required: true,
            validate: fn ($value) => $value ? null : 'Name is required'
        );

        $fromAirport = text(
            label: 'From airport(s) (IATA code, comma-separated)',
            placeholder: 'e.g., STN or LON,LGW,STN',
            required: true,
            validate: function ($value) {
                if (!$value) {
                    return 'From airport is required';
                }
                $airports = array_map('trim', array_map('strtoupper', explode(',', $value)));
                foreach ($airports as $airport) {
                    $exists = spin(
                        fn () => Airport::where('iata', $airport)->exists(),
                        "Validating {$airport}..."
                    );
                    if (!$exists) {
                        return "Airport {$airport} not found in database";
                    }
                }
                return null;
            }
        );
        $fromAirport = strtoupper($fromAirport);

        $toAirport = text(
            label: 'To airport(s) (IATA code, comma-separated)',
            placeholder: 'e.g., FAO or FAO,LIS',
            required: true,
            validate: function ($value) {
                if (!$value) {
                    return 'To airport is required';
                }
                $airports = array_map('trim', array_map('strtoupper', explode(',', $value)));
                foreach ($airports as $airport) {
                    $exists = spin(
                        fn () => Airport::where('iata', $airport)->exists(),
                        "Validating {$airport}..."
                    );
                    if (!$exists) {
                        return "Airport {$airport} not found in database";
                    }
                }
                return null;
            }
        );
        $toAirport = strtoupper($toAirport);

        // Auto-generate slug if not provided
        if (empty($slug)) {
            $slug = Str::slug("{$fromAirport}-{$toAirport}");
        }

        // Check if slug already exists
        if (FlightCalendar::where('slug', $slug)->exists()) {
            warning("Calendar with slug '{$slug}' already exists!");
            return;
        }

        $airlinesInput = text(
            label: 'Airlines (comma-separated codes, leave empty for all airlines)',
            placeholder: 'e.g., FR,FY or leave empty',
            required: false
        );
        $airlines = $airlinesInput ? array_map('trim', array_map('strtoupper', explode(',', $airlinesInput))) : [];

        $maxStops = text(
            label: 'Max stops',
            default: '0',
            validate: function ($value) {
                if (!is_numeric($value)) {
                    return 'Max stops must be a number';
                }
                if ($value < 0 || $value > 3) {
                    return 'Max stops must be between 0 and 3';
                }
                return null;
            }
        );

        $isActive = confirm(
            label: 'Is this calendar active?',
            default: true
        );

        $calendar = FlightCalendar::create([
            'slug' => $slug,
            'name' => $name,
            'from_airport' => $fromAirport,
            'to_airport' => $toAirport,
            'airlines' => $airlines,
            'max_stops' => (int)$maxStops,
            'is_active' => $isActive,
        ]);

        info("Calendar '{$calendar->name}' created successfully with slug '{$calendar->slug}'!");
        $this->newLine();
    }

    protected function updateCalendar(): void
    {
        $calendars = FlightCalendar::all();

        if ($calendars->isEmpty()) {
            warning('No flight calendars found.');
            return;
        }

        $options = $calendars->mapWithKeys(fn ($cal) => [$cal->id => "{$cal->name} ({$cal->slug})"]);

        $calendarId = select(
            label: 'Select calendar to update',
            options: $options->toArray()
        );

        $calendar = FlightCalendar::find($calendarId);

        $name = text(
            label: 'Calendar name',
            default: $calendar->name,
            required: true
        );

        $airlinesInput = text(
            label: 'Airlines (comma-separated codes, leave empty for all airlines)',
            default: implode(',', $calendar->airlines),
            required: false
        );
        $airlines = $airlinesInput ? array_map('trim', array_map('strtoupper', explode(',', $airlinesInput))) : [];

        $maxStops = text(
            label: 'Max stops',
            default: (string)$calendar->max_stops,
            validate: function ($value) {
                if (!is_numeric($value)) {
                    return 'Max stops must be a number';
                }
                if ($value < 0 || $value > 3) {
                    return 'Max stops must be between 0 and 3';
                }
                return null;
            }
        );

        $isActive = confirm(
            label: 'Is this calendar active?',
            default: $calendar->is_active
        );

        $calendar->update([
            'name' => $name,
            'airlines' => $airlines,
            'max_stops' => (int)$maxStops,
            'is_active' => $isActive,
        ]);

        info("Calendar '{$calendar->name}' updated successfully!");
        $this->newLine();
    }

    protected function deleteCalendar(): void
    {
        $calendars = FlightCalendar::all();

        if ($calendars->isEmpty()) {
            warning('No flight calendars found.');
            return;
        }

        $options = $calendars->mapWithKeys(fn ($cal) => [$cal->id => "{$cal->name} ({$cal->slug})"]);

        $calendarId = select(
            label: 'Select calendar to delete',
            options: $options->toArray()
        );

        $calendar = FlightCalendar::find($calendarId);
        $pricesCount = $calendar->prices()->count();
        $historiesCount = $calendar->histories()->count();

        if ($pricesCount > 0 || $historiesCount > 0) {
            warning("This calendar has {$pricesCount} price records and {$historiesCount} history records.");
            warning('Deleting the calendar will also delete all associated data (CASCADE).');
        }

        $confirmed = confirm(
            label: "Are you sure you want to delete '{$calendar->name}'?",
            default: false
        );

        if ($confirmed) {
            $calendar->delete();
            info("Calendar '{$calendar->name}' deleted successfully!");
        } else {
            info('Deletion cancelled.');
        }

        $this->newLine();
    }
}

