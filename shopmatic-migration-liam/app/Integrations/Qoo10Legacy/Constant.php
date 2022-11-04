<?php


namespace App\Integrations\Qoo10Legacy;

use App\Constants\CategoryAttributeLevel;
use App\Constants\CategoryAttributeType;
use App\Constants\ProductPriceType;
use App\Integrations\AbstractConstant;
use App\Models\Integration;

class Constant extends AbstractConstant
{
    public static function PRICES($valueOnly = false)
    {
        if ($valueOnly) {
            return [
                ProductPriceType::SELLING()->getValue(),
                ProductPriceType::RETAIL()->getValue(),
            ];
        }
        return [
            ProductPriceType::SELLING(),
            ProductPriceType::RETAIL(),
        ];
    }

    public static function ATTRIBUTES()
    {
        $attributes = [
            ['name' => 'goods_weight', 'label' => 'Weight input at Kg', 'required' => 1, 'type' => CategoryAttributeType::NUMERIC()->getValue()],
            ['name' => 'industry_code', 'label' => 'Industry Code'],
            ['name' => 'industry_code_type', 'label' => 'Industry Code Type', 'data' => [['name' => 'UPCCode', 'value' => 'UPC'], ['name' => 'JANCode', 'value' => 'JAN'], ['name' => 'KANCode', 'value' => 'KAN'], ['name' => 'ISBN', 'value' => 'ISBN'], ['name' => 'EANCode', 'value' => 'EAN'], ['name' => 'HSCode', 'value' => 'HS']], 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
//            ['name' => 'qoo10brand', 'label' => 'Brand'],
//            ['name' => 'manufacturer', 'label' => 'Manufacturer'],
            ['name' => 'simple_code', 'label' => 'Reference ID Code - Simple Code'],
            ['name' => 'other_code', 'label' => 'Reference ID Code - Other Site Reference Code'],
//            ['name' => 'address', 'label' => 'After Sales Service - Address'], // qoo10 cant fill in this in New Listing & Edit Listing page
            ['name' => 'phone_number', 'label' => 'After Sales Service - Phone Number'],
            ['name' => 'email', 'label' => 'After Sales Service - Email'],
            ['name' => 'product_model_no', 'label' => 'Product Model No'],
            ['name' => 'manufacture_year', 'label' => 'Manufacture Date - Year', 'data' => json_decode('["2020","2019","2018","2017","2016","2015","2014","2013","2012","2011","2010","2009","2008","2007","2006","2005","2004","2003","2002","2001"]',true), 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'manufacture_month', 'label' => 'Manufacture Date - Month', 'data' => json_decode('[{"name": "Jan", "value": "01"},{"name": "Feb", "value": "02"},{"name": "Mar", "value": "03"},{"name": "Apr", "value": "04"},{"name": "May", "value": "05"},{"name": "Jun", "value": "06"},{"name": "Jul", "value": "07"},{"name": "Aug", "value": "08"},{"name": "Sep", "value": "09"},{"name": "Oct", "value": "10"},{"name": "Nov", "value": "11"},{"name": "Dec", "value": "12"}]',true), 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'material', 'label' => 'Material'],
            ['name' => 'additional_info_title', 'label' => 'Additional Info - Title'],
            ['name' => 'additional_info_desc', 'label' => 'Additional Info - Description'],
//            ['name' => 'sell_format', 'label' => 'Sell Format', 'data' => json_decode('[{"name": "Buy Now(9%~12%)"},{"name": "Group Buy(6% or 8% Service Fee)"},{"name": "Auction(6%)"},{"name": "Lucky Auction(6%)"}]',true), 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'delivery_type', 'label' => 'Delivery Type', 'data' => json_decode('[{"name": "Delivery", "value": "BI"},{"name": "e-Ticket", "value": "EC"},{"name": "Online Service", "value": "OS"}]',true), 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'item_condition', 'label' => 'Item Condition', 'data' => json_decode('[{"name": "New Item", "value": "10"},{"name": "Used Item", "value": "30"}]',true), 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'condition', 'label' => 'Condition', 'data' => json_decode('[{"name": "Refurbish", "value": "36"},{"name": "not used", "value": "31"},{"name": "like new", "value": "32"},{"name": "good", "value": "33"},{"name": "some wear", "value": "34"},{"name": "Unusable(collectors only)", "value": "35"},{"name": "None", "value": "00"}]',true), 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'period_of_use', 'label' => 'Period Of Use'],
            ['name' => 'brief_explanation', 'label' => 'Brief Explanation'],
            ['name' => 'adult_item', 'label' => 'Adult Item?', 'data' => [['name' => 'Yes', 'value' => 'Y'], ['name' => 'No', 'value' => 'N']], 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'short_title', 'label' => 'Short Title'],
            ['name' => 'video', 'label' => 'Video Url'],
            ['name' => 'header', 'label' => 'Header', 'type' => CategoryAttributeType::RICH_TEXT()->getValue()],
            ['name' => 'footer', 'label' => 'Footer', 'type' => CategoryAttributeType::RICH_TEXT()->getValue()],
            ['name' => 'option_order', 'label' => 'Sorting Type', 'data' => [['name' => 'As registered by seller', 'value' => 'S'], ['name' => 'Ordered by Price', 'value' => 'P'], ['name' => 'Ordered by Alphabet', 'value' => 'N']], 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'gift', 'label' => 'Gift'],
            ['name' => 'min_order_qty', 'label' => 'Minimum Order Limit'],
            ['name' => 'available_period', 'label' => 'Available Period', 'type' => CategoryAttributeType::DATETIME()],
            ['name' => 'restock_mail', 'label' => 'Send Notes To Buyer When Sold Out', 'data' => [['name' => 'Yes', 'value' => 'Y'], ['name' => 'No', 'value' => 'N']], 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'restock_memo', 'label' => 'Notes To Display When Sold Out'],
            ['name' => 'image_type', 'label' => 'Item Image\'s Type', 'data' => [['name' => 'Use Square Image ( 800 X 800 )', 'value' => 'S'], ['name' => 'Use Tall Rectangle Type Image (612 X 800)', 'value' => 'R']], 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'variant_image_type', 'label' => 'Option Image Display (Variant Image Display Type)', 'data' => [['name' => 'Big Thumbnail', 'value' => 'L'], ['name' => 'Small Thumbnail', 'value' => 'T'], ['name' => 'Option Select Box', 'value' => 'B'], ['name' => 'No Use', 'value' => 'N']], 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'origin_type', 'label' => 'Production Place - Type', 'data' => [['name' => 'Domestic', 'value' => 'K'], ['name' => 'Overseas', 'value' => 'F'], ['name' => 'Others', 'value' => 'U']], 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'origin', 'label' => 'Production Place - Country Name'],
            ['name' => 'hs_code', 'label' => 'HS Code', 'level' => 1],
            ['name' => 'available_shipping_date', 'label' => 'Available Shipping Date', 'level' => 1, 'type' => CategoryAttributeType::DATETIME()],
        ];

        $attributes = collect($attributes)->map(function ($item) {
            $data["integration_id"] = Integration::QOO10_LEGACY;
            $data["name"] = $item['name'];
            $data["label"] = $item['label'];
            $data["required"] = $item['required'] ?? 0;
            $data["data"] = $item['data'] ?? null;
            $data["additional_data"] = $item['additional_data'] ?? null;
            $data["attribute_type"] = $item['attribute_type'] ?? 0;
            $data["type"] = $item['type'] ?? CategoryAttributeType::TEXT()->getValue();
            $data["level"] = $item['level'] ?? CategoryAttributeLevel::GENERAL()->getValue();

            return $data;
        })->toArray();

        return $attributes;
    }

    /**
     * Combinesell's attributes name => qoo10's single product's key
     *
     * @param bool $variantMode
     * @return array
     */
    public static function MAPATTRIBUTESKEY($variantMode = false)
    {
        if (!$variantMode) {
            return [
                // main product attributes
                'goods_weight' => 'gd_weight',
                'industry_code' => 'gd_ind_code',
                'industry_code_type' => 'gd_ind_code_type',
                'simple_code' => 'simple_cd',
                'other_code' => 'gd_ref_code',
//            'address' => 'as_detail', // not sure is as_detail or not, not using this currently
                'phone_number' => 'as_phone',
                'email' => 'as_email',
                'product_model_no' => 'model_nm',
                'manufacture_date' => 'gd_made_sdt', // TODO: change month and year to date
                'material' => 'gd_material',
                'delivery_type' => 'gd_type',
                'item_condition' => 'gd_kind_1',
                'condition' => 'gd_kind_2',
                'period_of_use' => 'used_period',
                'brief_explanation' => 'used_stat_info',
                'adult_item' => 'adult_yn',
                'short_title' => 'gd_short_nm',
                'video' => 'video_url',
                'header' => 'detail_header_url',
                'footer' => 'detail_footer_url',
                'gift' => 'op_gd',
                'min_order_qty' => 'min_order_qty',
                'available_period' => 'expire_dt',
                'restock_mail' => 'send_restock_mail_yn',
                'restock_memo' => 'restock_memo',
                'image_type' => 'image_type',
                'origin_type' => 'gd_origin2',
                'origin' => 'gd_origin',

                // cant get this in extracted single product data (Images section)
//            'additional_info_title' => 'new_good_properties_names',
//            'additional_info_desc' => 'new_good_properties_values',

                // can get it via. Edit Listing page > Options, but currently, single product data is extracted from Images
//            'option_order' => 'option_order',
//            'variant_image_type' => 'display_type',
            ];
        } else {
            return [
                // variant attributes
                'hs_code' => 'gd_ind_code',
                'available_shipping_date' => 'available_ship_dt',
            ];
        }
    }
}
