<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $flight_calendar_id
 * @property \Illuminate\Support\Carbon $date
 * @property float $highest_price_gbp
 * @property float $average_price_gbp
 * @property int $flight_count
 * @property \Illuminate\Support\Carbon $created_at
 */
class FlightCalendarHistory extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'flight_calendar_id',
        'date',
        'highest_price_gbp',
        'average_price_gbp',
        'flight_count',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the calendar this history belongs to.
     */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(FlightCalendar::class, 'flight_calendar_id');
    }
}

