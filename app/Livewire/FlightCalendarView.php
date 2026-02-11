<?php

namespace App\Livewire;

use App\Models\FlightCalendar;
use App\Models\FlightPrice;
use App\Services\UkHolidaysService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Component;

class FlightCalendarView extends Component
{
    public FlightCalendar $calendar;
    public ?FlightPrice $selectedFlight = null;

    protected UkHolidaysService $holidaysService;

    public function boot(UkHolidaysService $holidaysService)
    {
        $this->holidaysService = $holidaysService;
    }

    public function mount(FlightCalendar $calendar)
    {
        $this->calendar = $calendar;
    }

    public function getCalendarWeeksProperty(): array
    {
        $startDate = CarbonImmutable::today();
        $endDate = $startDate->addDays($this->calendar->days_ahead - 1);

        // Start from Monday of the week containing the start date
        $currentDate = $startDate->startOfWeek();

        $weeks = [];
        $week = [];

        while ($currentDate <= $endDate || count($week) > 0) {
            // Add day to current week
            $week[] = [
                'date' => $currentDate,
                'inRange' => $currentDate >= $startDate && $currentDate <= $endDate,
                'dayType' => $this->holidaysService->getDayType($currentDate),
            ];

            // If we've completed a week (Sunday), start a new week
            if ($currentDate->isSunday()) {
                $weeks[] = $week;
                $week = [];
            }

            $currentDate = $currentDate->addDay();

            // Break if we're past the end date and completed the week
            if ($currentDate > $endDate && count($week) === 0) {
                break;
            }
        }

        // Add any remaining days in the final week
        if (count($week) > 0) {
            $weeks[] = $week;
        }

        return $weeks;
    }

    public function getFlightsByDateProperty(): Collection
    {
        $startDate = CarbonImmutable::today()->startOfDay();
        $endDate = $startDate->addDays($this->calendar->days_ahead);

        return FlightPrice::where('flight_calendar_id', $this->calendar->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('price_gbp')
            ->get()
            ->groupBy(fn ($flight) => $flight->date->format('Y-m-d'));
    }

    public function selectFlight(int $flightId)
    {
        $this->selectedFlight = FlightPrice::find($flightId);
    }

    public function closeModal()
    {
        $this->selectedFlight = null;
    }

    public function render()
    {
        return view('livewire.flight-calendar-view', [
            'calendarWeeks' => $this->calendarWeeks,
            'flightsByDate' => $this->flightsByDate,
        ]);
    }
}



