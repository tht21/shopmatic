<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\IntegrationCategoryAttribute
 *
 * @property int $id
 * @property int $integration_category_id
 * @property int $integration_id
 * @property string $name
 * @property string $label
 * @property int $required
 * @property array|null $data
 * @property array|null $additional_data
 * @property string|null $html_hint
 * @property int $type
 * @property int $level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $order
 * @property int $section
 * @property string|null $external_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereHtmlHint($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereIntegrationCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategoryAttribute whereSection($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class IntegrationCategoryAttribute extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'external_id', 'name', 'label', 'type', 'level', 'required', 'data',  'additional_data', 'html_hint',
        'integration_category_id', 'integration_id', 'order', 'section', 'is_sale_prop'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'additional_data' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'integration_id', 'integration_category_id',
    ];

    public function integrationCategory() {

        return $this->belongsTo(IntegrationCategory::class);
    }

    /**
     * check attribute have is_sale_prop = 1
     *
     */
     public function isSaleProp() {

        if (!empty($this->additional_data) && !empty($this->additional_data['is_sale_prop']) && $this->additional_data['is_sale_prop'] == 1) {
            return true;
        }
        return false;
     }

}
