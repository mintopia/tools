<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $flight_calendar_id
 * @property \Illuminate\Support\Carbon $date
 * @property string $flight_number
 * @property string $departure_time
 * @property string $arrival_time
 * @property float $price_gbp
 * @property string $currency
 * @property array $raw_data
 * @property \Illuminate\Support\Carbon $created_at
 */
class FlightPrice extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'flight_calendar_id',
        'date',
        'flight_number',
        'departure_time',
        'arrival_time',
        'price_gbp',
        'currency',
        'raw_data',
    ];

    protected $casts = [
        'date' => 'date',
        'raw_data' => 'array',
    ];

    /**
     * Get the calendar this price belongs to.
     */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(FlightCalendar::class, 'flight_calendar_id');
    }

    /**
     * Scope a query to only include prices for a specific date.
     */
    public function scopeForDate($query, CarbonImmutable $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope a query to only include prices for a specific calendar.
     */
    public function scopeForCalendar($query, int $calendarId)
    {
        return $query->where('flight_calendar_id', $calendarId);
    }

    /**
     * Scope a query to only include prices before a specific date.
     */
    public function scopeBeforeDate($query, CarbonImmutable $date)
    {
        return $query->where('date', '<', $date);
    }
}

