<?php
namespace App\Http\Controllers;
class FlightController extends Controller
{
    public function search()
    {
        return view('flights.search');
    }
}
