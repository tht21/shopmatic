<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Customer
 *
 * @property int $id
 * @property string|null $source
 * @property int $shop_id
 * @property int|null $source_account_id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone_number
 * @property mixed|null $addresses
 * @property float $total_value_ordered
 * @property float $total_orders
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereAddresses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereSourceAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereTotalOrders($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereTotalValueOrdered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class Customer extends Model
{
    //
}
