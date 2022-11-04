<?php

namespace App\Models;

use App\Constants\ContactType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\Contact
 *
 * @property int $id
 * @property array|null $external_ids
 * @property int $shop_id
 * @property string $name
 * @property string|null $contact_name
 * @property string|null $contact_number
 * @property string|null $contact_email
 * @property array|null $full_address
 * @property string|null $address_1
 * @property string|null $address_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postcode
 * @property string|null $country
 * @property int $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Contact onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereAddress1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereAddress2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereContactNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereExternalIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Contact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Contact withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Contact withoutTrashed()
 * @mixin \Eloquent
 */
class Contact extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'external_ids', 'name', 'full_address', 'address_1', 'address_2', 'city', 'state', 'postcode',
        'country', 'type', 'contact_name','contact_number', 'contact_email'
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'external_ids' => 'array',
        'full_address' => 'array',
    ];

    /**
     * Retrieve the external_id given the account id
     *
     * @param $accountId
     * @return mixed|null
     */
    public function getExternalId($accountId)
    {
        return $this->external_ids[$accountId] ?? null;
    }

    /**
     * Creates the contact
     *
     * @param $order
     * @param null $externalId
     * @return
     */
    public static function createFromOrder($order, $externalId = null)
    {
        $contact = Contact::firstOrCreate([
            'shop_id' => $order->shop_id,
            'name' => ucwords(strtolower($order->customer_name)),
            'contact_name' => ucwords(strtolower($order->customer_name)),
            'contact_email' => strtolower($order->customer_email),
            'type' => ContactType::CUSTOMER(),
        ]);
        if (!empty($externalId) && $order->account_id) {
            $contact->external_ids[$order->account_id] = $externalId;
        }
        if ($address = $order->shipping_address) {
            if (empty($contact->full_address)) {
                $contact->full_address = $address;

                $contact->city = $address['city'];
                $contact->postcode = $address['postcode'];
                $contact->state = $address['state'];
                $contact->country = $address['country'];
            }

            if (empty($contact->contact_number)) {
                $contact->contact_number = $address['phone_number'];
            }
        }

        $contact->save();
        return $contact;
    }
}
