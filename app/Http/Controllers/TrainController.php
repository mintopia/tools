<?php
namespace App\Http\Controllers;

use App\Models\TrainStation;
use App\Services\RealTimeTrains\RealTimeTrainsService;
use Illuminate\Http\Request;

class TrainController extends Controller
{
    public function nextFastest(Request $request, RealTimeTrainsService $rtt)
    {
        $fromStation = null;
        $toStation = null;
        $trains = collect();

        if ($request->has('from') && $request->has('to')) {
            $validated = $request->validate([
                'from' => 'required|string|max:255',
                'to' => 'required|string|max:255',
            ]);

            // Find stations by CRS code or name
            $fromStation = TrainStation::where('crs', strtoupper($validated['from']))
                ->orWhere('name', 'like', $validated['from'])
                ->first();

            $toStation = TrainStation::where('crs', strtoupper($validated['to']))
                ->orWhere('name', 'like', $validated['to'])
                ->first();

            if (!$fromStation) {
                return back()->withErrors(['from' => 'Station not found']);
            }

            if (!$toStation) {
                return back()->withErrors(['to' => 'Station not found']);
            }

            $trains = $rtt->nextFastestTrains($fromStation, $toStation);
        }

        return view('trains.next-fastest', [
            'fromStation' => $fromStation,
            'toStation' => $toStation,
            'trains' => $trains,
            'excluded' => collect(),
        ]);
    }
}
