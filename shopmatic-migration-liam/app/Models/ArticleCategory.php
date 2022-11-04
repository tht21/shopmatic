<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\ArticleCategory
 *
 * @property int $id
 * @property string $name
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-write mixed $raw
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleCategory newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ArticleCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleCategory query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleCategory whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ArticleCategory withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ArticleCategory withoutTrashed()
 * @mixin \Eloquent
 */
class ArticleCategory extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable, SoftDeletes;

    protected $fillable = ['name', 'status'];
}
