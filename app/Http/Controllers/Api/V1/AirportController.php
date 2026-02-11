<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AirportResource;
use App\Models\Airport;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 20);
        $search = $request->input('search');

        $query = Airport::query();

        if ($search) {
            if (strlen($search) === 3) {
                $query = $query->where('iata', $search);
            } else {
                $query = $query
                    ->where('name', 'like', "%$search%")
                    ->orWhere('iata', 'like', "%$search%");
            }
        }

        return $query->paginate(perPage: $perPage, page: $page)->toResourceCollection(AirportResource::class);
    }

    public function show(Airport $airport)
    {
        return $airport->toResource(AirportResource::class);
    }
}

