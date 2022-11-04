<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\Region
 *
 * @property int $id
 * @property string $name
 * @property string|null $thumbnail_image
 * @property int $position
 * @property int $visibility
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereThumbnailImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereVisibility($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class Region extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;

    const GLOBAL = 1;
    const SINGAPORE = 2;
    const MALAYSIA = 3;
    const INDONESIA = 4;

    const REGIONS = [
        self::GLOBAL => 'Global',
        self::SINGAPORE => 'Singapore',
        self::MALAYSIA => 'Malaysia',
        self::INDONESIA => 'Indonesia',
    ];

    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'thumbnail_image', 'position', 'visibility','shortcode','currency',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'visibility'
    ];

    /**
     * START - Relationship Methods
     */

    /**
     * END - Relationship Methods
     */

}
