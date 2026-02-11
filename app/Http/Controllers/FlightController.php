<?php
namespace App\Http\Controllers;

use App\Models\FlightCalendar;
use App\Models\FlightPrice;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class FlightController extends Controller
{
    public function search()
    {
        return view('flights.search');
    }

    public function calendar(FlightCalendar $calendar)
    {
        return view('flights.calendar', ['calendar' => $calendar]);
    }

    public function exportIcal(Request $request, FlightCalendar $calendar)
    {
        // Get filter parameters from request
        $airlines = $request->input('airlines') ? explode(',', $request->input('airlines')) : [];
        $airports = $request->input('airports') ? explode(',', $request->input('airports')) : [];
        $depTimeMin = (int) $request->input('depTimeMin', 0);
        $depTimeMax = (int) $request->input('depTimeMax', 1439);
        $arrTimeMin = (int) $request->input('arrTimeMin', 0);
        $arrTimeMax = (int) $request->input('arrTimeMax', 1439);

        // Fetch flights for the calendar date range
        $startDate = CarbonImmutable::today()->startOfDay();
        $endDate = $startDate->addDays($calendar->days_ahead);

        $query = FlightPrice::where('flight_calendar_id', $calendar->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('price_gbp');

        $flights = $query->get();

        // Apply filters
        $filteredFlights = $flights->filter(function ($flight) use ($airlines, $airports, $depTimeMin, $depTimeMax, $arrTimeMin, $arrTimeMax) {
            $flightData = $flight->raw_data['flight'] ?? [];

            // Get flight details
            $airlineCode = $flightData['airline']['code'] ?? '';
            $fromAirport = $flightData['from']['code'] ?? '';
            $toAirport = $flightData['to']['code'] ?? '';

            $depTime = Carbon::parse($flight->departure_time);
            $depMinutes = $depTime->hour * 60 + $depTime->minute;

            $arrTime = Carbon::parse($flight->arrival_time);
            $arrMinutes = $arrTime->hour * 60 + $arrTime->minute;

            // Apply airline filter
            if (!empty($airlines) && !in_array($airlineCode, $airlines)) {
                return false;
            }

            // Apply airport filter
            if (!empty($airports)) {
                $hasAirport = in_array($fromAirport, $airports) || in_array($toAirport, $airports);
                if (!$hasAirport) {
                    return false;
                }
            }

            // Apply departure time filter
            if ($depMinutes < $depTimeMin || $depMinutes > $depTimeMax) {
                return false;
            }

            // Apply arrival time filter
            if ($arrMinutes < $arrTimeMin || $arrMinutes > $arrTimeMax) {
                return false;
            }

            return true;
        });

        // Create iCal calendar
        $icalCalendar = Calendar::create($calendar->name)
            ->description("Flight calendar for {$calendar->from_airport} to {$calendar->to_airport}")
            ->productIdentifier('Mintopia Tools');

        // Add each flight as an event
        foreach ($filteredFlights as $flight) {
            $flightData = $flight->raw_data['flight'] ?? [];

            $airlineName = $flightData['airline']['name'] ?? '';
            $airlineCode = $flightData['airline']['code'] ?? '';
            $fromAirportName = $flightData['from']['name'] ?? '';
            $fromAirportCode = $flightData['from']['code'] ?? '';
            $toAirportName = $flightData['to']['name'] ?? '';
            $toAirportCode = $flightData['to']['code'] ?? '';
            $duration = $flightData['duration'] ?? 0;

            // Parse departure and arrival times with date
            $departureDateTime = Carbon::parse($flight->date->format('Y-m-d') . ' ' . $flight->departure_time, 'UTC');
            $arrivalDateTime = Carbon::parse($flight->date->format('Y-m-d') . ' ' . $flight->arrival_time, 'UTC');

            // Handle overnight flights (arrival before departure means next day)
            if ($arrivalDateTime < $departureDateTime) {
                $arrivalDateTime->addDay();
            }

            // Create event summary
            $summary = "{$flight->flight_number}: {$fromAirportCode} → {$toAirportCode}";

            // Create detailed description
            $description = "Flight: {$flight->flight_number}\n";
            $description .= "Airline: {$airlineName} ({$airlineCode})\n";
            $description .= "From: {$fromAirportName} ({$fromAirportCode})\n";
            $description .= "To: {$toAirportName} ({$toAirportCode})\n";
            $description .= "Duration: " . floor($duration / 60) . "h " . ($duration % 60) . "m\n";
            $description .= "Price: £" . number_format($flight->price_gbp, 2);

            // Create event
            $event = Event::create()
                ->name($summary)
                ->description($description)
                ->startsAt($departureDateTime)
                ->endsAt($arrivalDateTime)
                ->address("{$fromAirportName} ({$fromAirportCode})");

            $icalCalendar->event($event);
        }

        // Generate iCal content
        $icalContent = $icalCalendar->get();

        // Return as downloadable .ics file
        return response($icalContent)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $calendar->slug . '.ics"');
    }
}
