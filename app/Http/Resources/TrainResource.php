<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'headCode' => $this->headCode,
            'operator' => $this->operator,
            'from' => [
                'station' => [
                    'crs' => $this->from->station->crs,
                    'name' => $this->from->station->name,
                ],
                'scheduled' => $this->from->scheduled?->toIso8601String(),
                'expected' => $this->from->expected?->toIso8601String(),
            ],
            'to' => [
                'station' => [
                    'crs' => $this->to->station->crs,
                    'name' => $this->to->station->name,
                ],
                'scheduled' => $this->to->scheduled?->toIso8601String(),
                'expected' => $this->to->expected?->toIso8601String(),
            ],
            'callingAt' => $this->callingAt->map(function ($stop) {
                return [
                    'station' => [
                        'crs' => $stop->station->crs,
                        'name' => $stop->station->name,
                    ],
                    'platform' => $stop->platform,
                    'scheduled' => $stop->scheduled?->toIso8601String(),
                    'expected' => $stop->expected?->toIso8601String(),
                ];
            })->toArray(),
        ];
    }
}

