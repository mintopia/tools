<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $iata
 * @property string $name
 * @property string|null $city
 * @property string|null $country
 * @property float|null $lat
 * @property float|null $lon
 * @property string|null $tz
 * @property bool $is_regional
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport whereIata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport whereTz($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Airport whereIsRegional($value)
 * @mixin \Eloquent
 */
class Airport extends Model
{
    protected $fillable = [
        'iata',
        'name',
        'city',
        'country',
        'lat',
        'lon',
        'tz',
        'is_regional',
    ];

    protected $casts = [
        'is_regional' => 'boolean',
        'lat' => 'float',
        'lon' => 'float',
    ];
}
