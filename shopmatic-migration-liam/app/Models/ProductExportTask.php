<?php

namespace App\Models;

use App\Constants\JobStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ProductExportTask
 *
 * @property int $id
 * @property int $shop_id
 * @property int|null $user_id
 * @property int $account_id
 * @property int $product_id
 * @property string $messages
 * @property array|null $settings
 * @property string $status
 * @property bool $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-write mixed $raw
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask whereMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductExportTask whereUserId($value)
 * @mixin \Eloquent
 */
class ProductExportTask extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'user_id', 'product_id', 'account_id', 'messages', 'settings', 'status'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'messages' => 'array',
        'settings' => 'array',
    ];

    /**
     * START - Accessor / Mutator
     */

    /**
     * Returns the human readable created_at timestamp
     *
     * @param $value
     *
     * @return boolean
     * @throws \Exception
     */
    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('g:i a, jS M Y');
    }

    /**
     * Mutates the status to a human readable status
     *
     * @param $value
     *
     * @return string
     */
    public function getStatusAttribute($value)
    {
        return ucwords(strtolower(JobStatus::search($value)));
    }

    /**
     * Implodes the messages array into a string
     *
     * @param $value
     *
     * @return string
     */
    public function getMessagesAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }

        // Some time the message is not start with '[' then it can not use as array.
        // Example: "{\"Header\":{\"DocumentVersion\":\"1.02\",\"MerchantIdentifier\":\"A11FHG2K4NXOY0\"},\"MessageType\":\"ProcessingReport\",\"Message\":{\"MessageID\":\"1\",\"ProcessingReport\":{\"DocumentTransactionID\":\"96388019129\",\"StatusCode\":\"Complete\",\"ProcessingSummary\":{\"MessagesProcessed\":\"1\",\"MessagesSuccessful\":\"0\",\"MessagesWithError\":\"1\",\"MessagesWithWarning\":\"0\"},\"Result\":{\"MessageID\":\"138214257\",\"ResultCode\":\"Error\",\"ResultMessageCode\":\"8560\",\"ResultDescription\":\"SKU Brids, Missing Attributes product_type. SKU Brids doesn't match any ASINs. Make sure that all standard product ids (such as UPC, ISBN, EAN, or JAN codes) are correct. To create a new ASIN, include the following attributes: product_type. Feed ID: 0. For more troubleshooting help, see http:\\/\\/sellercentral.amazon.sg\\/gp\\/errorcode\\/200692370\",\"AdditionalInfo\":{\"SKU\":\"Brids\"}}}}}"
        // So we need to check first to make sure we can use implode.
        if($value[0] == '[') {
            $message = implode(', ', json_decode($value, true));
            return $message;
        }
        return $value;
    }

    /**
     * END - Accessor / Mutator
     */
}
