<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AccountBrand
 *
 * @property int $id
 * @property int $shop_id
 * @property int|null $account_id
 * @property string|null $external_id
 * @property string $name
 * @property string|null $description
 * @property string|null $thumbnail_image
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand whereThumbnailImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountBrand whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AccountBrand extends Model
{
    //
}
