<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $crs
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainStation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainStation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainStation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainStation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainStation whereCrs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainStation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainStation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainStation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TrainStation extends Model
{
    //
}
