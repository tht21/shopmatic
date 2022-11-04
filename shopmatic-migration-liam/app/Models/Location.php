<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\Location
 *
 * @property int $id
 * @property int|null $linked_location_id
 * @property int $shop_id
 * @property int|null $account_id
 * @property string|null $external_id
 * @property string $label
 * @property string|null $contact_name
 * @property string|null $contact_number
 * @property string|null $contact_email
 * @property string|null $name
 * @property string|null $full_address
 * @property string|null $address_1
 * @property string|null $address_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postcode
 * @property string|null $country
 * @property int $has_inventory
 * @property int $type
 * @property int $position
 * @property array|null $attributes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Location onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereAddress1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereAddress2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereAttributes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereContactNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereHasInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereLinkedLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Location withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Location withoutTrashed()
 * @mixin \Eloquent
 */
class Location extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   `label` is used mainly for specifying what location it is. E.g. Headquarters / Kepong Branch and it is
     *      not used for the actual address sent / used in integrations.
     *
     * 2.   `has_inventory` is whether or not this location allows for inventory to be stored here.
     *      This is used for multi-location inventory
     *
     * 3.   `attributes` should be human readable values whenever possible, as we should probably display all these
     *      attributes to users.
     *
     * 4.   `position` is used to sort the locations. If any data requires the user to select from the list of
     *      locations, it should sort by `position` in ascending order.
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'linked_location_id', 'account_id', 'external_id', 'label', 'name', 'full_address', 'address_1',
        'address_2', 'city', 'state', 'postcode', 'country', 'has_inventory', 'type', 'attributes', 'contact_name',
        'contact_number', 'contact_email', 'position'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'attributes' => 'array',
    ];

}
