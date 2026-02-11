<?php
namespace App\Http\Controllers;

use App\Services\FlightSearch\BasicSearch;
use Mintopia\LaravelFlights\FlightService;

class FlightController extends Controller
{
    public function search()
    {
        return view('flights.search');
    }
}
