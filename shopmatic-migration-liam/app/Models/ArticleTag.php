<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * App\Models\ArticleTag
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Article[] $articles
 * @property-read int|null $articles_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-write mixed $raw
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleTag newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ArticleTag onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleTag query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleTag whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleTag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ArticleTag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ArticleTag withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ArticleTag withoutTrashed()
 * @mixin \Eloquent
 */
class ArticleTag extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable, SoftDeletes, HasSlug;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'slug'
    ];

    /**
     * Get the options for generating the slug.
     *
     * @return SlugOptions
     */
    public function getSlugOptions()
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['id', 'name'])
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_article_tag');
    }
}

