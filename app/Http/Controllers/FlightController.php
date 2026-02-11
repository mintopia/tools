<?php
namespace App\Http\Controllers;

use App\Models\FlightCalendar;
use App\Services\FlightSearch\BasicSearch;
use Mintopia\LaravelFlights\FlightService;

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
}
