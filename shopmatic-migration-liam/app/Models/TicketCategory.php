<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\TicketCategory
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TicketCategory[] $children
 * @property-read int|null $children_count
 * @property-read \App\Models\TicketCategory $parent
 * @property-write mixed $raw
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketCategory newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TicketCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketCategory query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketCategory whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TicketCategory withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TicketCategory withoutTrashed()
 * @mixin \Eloquent
 */
class TicketCategory extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable, SoftDeletes;

    protected $fillable = ['name', 'status', 'parent_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function parent()
    {
        return $this->hasOne(TicketCategory::class, 'id', 'parent_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(TicketCategory::class, 'parent_id', 'id' );
    }
}
