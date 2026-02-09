<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TrainStation\IndexRequest;
use App\Http\Resources\Api\V1\TrainStationResource;
use App\Models\TrainStation;

class TrainStationController extends Controller
{
    public function index(IndexRequest $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 20);
        $search = $request->input('search');
        $query = TrainStation::query();
        if ($search) {
            $query = $query
                ->where('name', 'like', "%$search%")
                ->orWhere('crs', 'like', "%$search%");
        }
        return $query->paginate(perPage: $perPage, page: $page)->toResourceCollection(TrainStationResource::class);
    }

    public function show(TrainStation $trainstation)
    {
        return $trainstation->toResource(TrainStationResource::class);
    }
}
