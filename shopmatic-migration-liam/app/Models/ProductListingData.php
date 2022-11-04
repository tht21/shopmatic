<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ProductListingData
 *
 * @property int $id
 * @property int $product_listing_id
 * @property array|null $raw_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListingData newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListingData newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListingData query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListingData whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListingData whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListingData whereProductListingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListingData whereRawData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListingData whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class ProductListingData extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_listing_id', 'raw_data'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'raw_data' => 'array',
    ];

}
