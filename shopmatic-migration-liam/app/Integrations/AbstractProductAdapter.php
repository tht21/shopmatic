<?php

namespace App\Integrations;


use App\Constants\CategoryAttributeType;
use App\Constants\IntegrationSyncData;
use App\Constants\ProductIdentifier;
use App\Constants\ProductStatus;
use App\Events\IntegrationCategoryUpdated;
use App\Events\NewProductAlert;
use App\Factories\ClientFactory;
use App\Constants\ProductAlertType;
use App\Factories\ConstantFactory;
use App\Models\Region;
use App\Interfaces\ProductAdapterInterface;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\Brand;
use App\Models\Integration;
use App\Models\IntegrationCategory;
use App\Models\IntegrationCategoryAttribute;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductListing;
use App\Models\ProductPrice;
use App\Utilities\InternalResponse;
use App\Utilities\SubscriptionHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


abstract class AbstractProductAdapter extends InternalResponse implements ProductAdapterInterface
{

    /**
     * @var Account
     */
    protected $account;

    protected $client;

    protected $constant;

    protected $handledProducts = [];

    /**
     * ProductAdapter constructor.
     *
     * @param Account|null $account
     *
     * @throws \Exception
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
        $this->client = ClientFactory::create($account);
        $this->constant = ConstantFactory::create($account);
    }

    /**
     * Returns the Client object for the account
     *
     * @return AbstractClient|object
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the Account Attributes
     *
     * @return array
     */
    public function getIntegrationAttributes()
    {
        return $this->retrieveAttributes();
    }

    /**
     * Transforms the attributes if required
     *
     * @return array
     */
    public function transformAttributes($attributes)
    {
        return $attributes;
    }

    /**
     * Retrieves all the field attributes
     * NOTE: Every array item here MUST be converted to array - if you're using collect() always do a ->toArray()
     *
     * @return array
     */
    public function retrieveAttributes()
    {
        return $this->constant::ATTRIBUTES();
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function retrieveBrands()
    {
        set_log_extra('account', $this->account);
        set_log_extra('integration', $this->account->integration);
        throw new \Exception('Integration does not support retrieving brands yet.');
    }

    /**
     * Retrieves logistics for the account
     *
     * @param null $attributes
     *
     * @return array
     * @throws \Exception
     */
    public function retrieveLogistics($attributes = null)
    {
        return [];
    }

    /**
     * Returns the Integration Price Type
     *
     * @return array
     */
    public function getPriceTypes()
    {
        return $this->constant::PRICES();
    }

    /**
     * This updates the brands(via Event)
     *
     * @throws \Exception
     */
    public function updateBrands()
    {
        $integration = $this->account->integration;
        if (!$integration->hasFeature($this->account->region_id, ['products', 'import_brands'])) {
            throw new \Exception('Integration does not support import brands. But retrieveBrands is called for ' . $integration->id);
        }

        $brands = $this->retrieveBrands();
        $externalIds = [];

        foreach ($brands as $brand) {
            $externalIds[] = $this->createBrand($brand);
        }
        // Delete un-used brand from integration
        Brand::where('integration_id', $integration->id)->whereNotIn('external_id', $externalIds)->delete();

        $integration->setSyncData($this->account->region_id, IntegrationSyncData::IMPORT_BRANDS(), now());
    }

    /**
     * Create brand
     *
     * @param $brand
     * @return mixed
     */
    public function createBrand($brand)
    {
        $brand = Brand::updateOrCreate([
            'external_id' => $brand['external_id'],
            'integration_id' => $this->account->integration_id,
            'region_id' => $this->account->region_id,
        ], [
            'name' => $brand['name']
        ]);

        return $brand['external_id'];
    }

    /**
     * This updates the categories and the attributes (via Event)
     *
     * @throws \Exception
     */
    public function updateCategories()
    {
        $integration = $this->account->integration;
        if (!$integration->hasFeature($this->account->region_id, ['products', 'import_categories'])) {
            throw new \Exception('Integration does not support import categories. But retrieveCategories is called for ' . $integration->id);
        }

        /*
         * The reason why we're fetching this is so it doesn't make a new query every time; It's MUCH faster to check
         * an array than to retrieve from the database for each entry
         */
        $oldExternalIds = $integration->categories()->whereRegionId($this->account->region_id)->with(['parent'])->active()->get()->keyBy('external_id')->toArray();

        $newExternalIds = [];

        $categories = $this->retrieveCategories();
        \Log::info('Update categories started for Integration Id|'.$this->account->integration_id.'|Region Id|'.$this->account->region_id);
        foreach ($categories as $category) {
            if ($this->account->integration_id === Integration::SHOPEE) {
                $integrationCategory = IntegrationCategory::where([
                    'external_id' => $category['external_id'],
                    'region_id' => $this->account->region_id,
                    'integration_id' => $this->account->integration_id
                ])->first();

                if (!$integrationCategory) {
                    $this->createCategory($category, null, $newExternalIds, $oldExternalIds);
                }
            } else {
                $this->createCategory($category, null, $newExternalIds, $oldExternalIds);
            }
        }

        //Retrieves only the deleted external ids
        $diffExternalIds = array_diff(array_diff(array_keys($oldExternalIds), $newExternalIds), $newExternalIds);
        foreach ($diffExternalIds as $diff) {

            /** @var IntegrationCategory $category */
            $category = IntegrationCategory::where(['external_id' => $diff, 'region_id' => $this->account->region_id, 'integration_id' => $integration->id])->active()->first();
            if ($category) {
                foreach ($category['children'] as $child) {
                    $child->update([ 'visible' => 0 ]);
                }
                $category->update([ 'visible' => 0 ]);
            }

        }

        // Remove non-visible categories as it should all be in sync now.
        // $integration->categories()->where([
        //     'visible' => 0,
        //     'region_id' => $this->account->region_id,
        // ])->delete();

        // Update timestamp
        $integration->setSyncData($this->account->region_id, IntegrationSyncData::IMPORT_CATEGORIES(), now());
        \Log::info('Update categories started for Integration Id|'.$this->account->integration_id.'|Region Id|'.$this->account->region_id);
    }


    /**
     * This updates the attributes for the IntegrationCategory
     *
     * @param IntegrationCategory $category
     * @throws \Throwable
     */
    public function updateCategoryAttribute(IntegrationCategory $category)
    {
        $attributes = $this->retrieveCategoryAttribute($category);

        // Deletes the old attributes to recreate
        // UNLESS we check differences based on name, type and data, which could be a hassle and might not be worth it
        $category->attributes()->delete();
        if ($attributes != null) {
            //TODO: See if we can map similarly named category attributes here
            DB::transaction(function () use ($category, $attributes) {
                try {
                    foreach ($attributes as $attribute) {
                        IntegrationCategoryAttribute::create(array_merge($attribute, [
                            'integration_category_id' => $category->id,
                            'integration_id'          => $category->integration_id,
                        ]));
                    }
                } catch (\Exception $e) {

                    // If there's an error with attribute fetching, we should delete this category so it can
                    // fetch this category, it's children and their attribute again
                    $category->deleteHierarchy();
                    throw $e;
                }
            });
        }
    }

    /**
     * This updates the account categories
     * Initiate from each integration Product Adapter
     *
     * @param $categories
     * @throws \Exception
     */
    public function updateAccountCategories($categories)
    {
        $integration = $this->account->integration;
        if (!$integration->hasFeature($this->account->region_id, ['products', 'import_account_categories'])) {
            throw new \Exception('Integration does not support import account categories. But retrieveAccountCategories is called for ' . $integration->id);
        }

        /*
         * The reason why we're fetching this is so it doesn't make a new query every time; It's MUCH faster to check
         * an array than to retrieve from the database for each entry
         */
        $oldExternalIds = $this->account->categories()->with(['parent'])->get()->keyBy('external_id')->toArray();
        $newExternalIds = [];

        foreach ($categories as $category) {
            $this->createAccountCategory($category, null, $newExternalIds, $oldExternalIds);
        }

        //Retrieves only the deleted external ids
        $diffExternalIds = array_diff(array_diff(array_keys($oldExternalIds), $newExternalIds), $newExternalIds);
        foreach ($diffExternalIds as $diff) {
            /** @var AccountCategory $category */
            $category = AccountCategory::where(['external_id' => $diff, 'account_id' => $this->account->id])->first();
            if ($category) {
                $category->deleteHierarchy();
            }
        }
        // Update timestamp
        $this->account->setSyncData(IntegrationSyncData::IMPORT_CATEGORIES(), now());
    }

    /**
     * Recursively creates the category if it doesn't exist and deletes existing categories which the hierarchy changed
     *
     * @param $category
     * @param $parent
     * @param $externalIds
     * @param $oldIds
     *
     * @throws \Exception
     */
    private function createCategory($category, $parent, &$externalIds, &$oldIds)
    {
        // This is because sometimes it will delete the hierarchy of categories, if so, the parent will be null
        if (!empty($parent)) {
            $parent = $parent->fresh();
            if (empty($parent)) {
                return;
            }
        }
        // Adds this category to the list of categories
        $externalIds[] = $category['external_id'];

        // Checking to make sure the parent category id didn't change
        if (array_key_exists($category['external_id'], $oldIds)) {

            //If parent's external_id is different, mark the old one as deleted, otherwise, do nothing
            if (!empty($parent) && $oldIds[$category['external_id']]['parent']['external_id'] != $parent['external_id']) {
                /** @var IntegrationCategory $oldCategory */
                $integrationCategory = $this->account->integration->categories()->where([
                    'external_id' => $category['external_id'],
                    'region_id' => $this->account->region_id,
                ])->active()->orderBy('id', 'asc')->first();

                // Just remap?
                if ($integrationCategory) {
                    $integrationCategory->parent_id = $parent->id;
                    $integrationCategory->save();
                } else {
                    unset($oldIds[$category['external_id']]);
                    $this->createCategory($category, $parent, $externalIds, $oldIds);
                    return;
                }
            } else {
                /** @var IntegrationCategory $integrationCategory */
                $integrationCategory = $this->account->integration->categories()->where([
                    'external_id' => $category['external_id'],
                    'region_id' => $this->account->region_id,
                ])->active()->orderBy('id', 'asc')->first();

                if (empty($integrationCategory)) {
                    set_log_extra('old_category', $oldIds[$category['external_id']]);
                    throw new \Exception('Unable to find integration category.');
                } else {
                    // update category detail if category exist
                    $integrationCategory->update(array_merge($category, [
                        'integration_id' => $this->account->integration_id,
                        'region_id' => $this->account->region_id,
                        'parent_id' => $parent ? $parent['id'] : null,
                        'visible' => 1,
                    ]));
                    $integrationCategory->touch();
                    // Dispatch event to do the rest (Update attribute & update linked category `category_attributes`
                    event(new IntegrationCategoryUpdated($integrationCategory));
                }
            }
        } else {
            $integrationCategory = IntegrationCategory::create(array_merge($category, [
                'integration_id' => $this->account->integration_id,
                'region_id' => $this->account->region_id,
                'parent_id' => $parent ? $parent['id'] : null,
                'visible' => 1,
            ]));

            // Dispatch event to do the rest (Update attribute & update linked category `category_attributes`
            event(new IntegrationCategoryUpdated($integrationCategory));
        }

        // Creates the children
        foreach ($category['children'] as $child) {
            $this->createCategory($child, $integrationCategory, $externalIds, $oldIds);
        }
    }

    /**
     * Recursively creates the account category if it doesn't exist and deletes existing categories which the hierarchy changed
     *
     * @param $category
     * @param $parent
     * @param $externalIds
     * @param $oldIds
     *
     * @throws \Exception
     */
    private function createAccountCategory($category, $parent, &$externalIds, &$oldIds)
    {
        // This is because sometimes it will delete the hierarchy of categories, if so, the parent will be null
        if (!empty($parent)) {
            $parent = $parent->fresh();
            if (empty($parent)) {
                return;
            }
        }
        // Adds this category to the list of categories
        $externalIds[] = $category['external_id'];

        // Checking to make sure the parent category id didn't change
        if (array_key_exists($category['external_id'], $oldIds)) {

            //If parent's external_id is different, mark the old one as deleted, otherwise, do nothing
            if (!empty($parent) && $oldIds[$category['external_id']]['parent']['external_id'] != $parent['external_id']) {
                /** @var AccountCategory $oldCategory */
                $oldCategory = $this->account->categories()->where('external_id', $category['external_id'])->first();
                // Might be deleted already from deleteHierarchy
                if ($oldCategory) {
                    $oldCategory->deleteHierarchy();
                }
                unset($oldIds[$category['external_id']]);

                $this->createAccountCategory($category, $parent, $externalIds, $oldIds);
                return;
            } else {
                if (!empty($oldIds[$category['external_id']]['parent'])) {
                    $accountCategory = AccountCategory::find($oldIds[$category['external_id']]['parent']['id']);
                    if (empty($accountCategory)) {
                        set_log_extra('old_account_category', $oldIds[$category['external_id']]);
                        throw new \Exception('Unable to find account category parent.');
                    }
                } else {
                    $accountCategory = null;
                }
            }
        } else {
            $accountCategory = new AccountCategory($category);
            $accountCategory->account_id = $this->account->id;
            $accountCategory->parent_id = $parent ? $parent['id'] : null;
            $accountCategory->save();

            // Dispatch event to do the rest (Update attribute & update linked category `category_attributes`
            //event(new IntegrationCategoryUpdated($accountCategory));
        }

        // Creates the children
        foreach ($category['children'] as $child) {
            $this->createAccountCategory($child, $accountCategory, $externalIds, $oldIds);
        }
    }

    /**
     * Remove deleted products
     *
     * @return bool
     * @throws \Exception
     */
    public function removeDeletedProducts()
    {
        // to prevent if this went wrong, and handledProducts was empty, can be removed fi firm there's no issue
        if (count($this->handledProducts) > 0) {
            $listings = $this->account->listings()->whereNull('product_variant_id')->whereNotIn('identifiers->external_id', $this->handledProducts)->get();

            foreach ($listings as $key => $listing) {
                $this->deleteProductListing($listing, true);

                if ($listing->product->listings()->count() === 0) {
                    $listing->product->status = ProductStatus::DRAFT();
                    $listing->product->save();
                }
            }
        }
    }

    /**
     * The function to check and delete the product listing
     *
     * @param ProductListing $productListing
     * @param bool $deleteListingVariants
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function deleteProductListing(ProductListing $productListing, $deleteListingVariants = false, $options = [])
    {
        // Options handle
        if (!empty($options)) {
            if (in_array('delete_integration_products', $options)) {
                //$this->delete($productListing);
            } else {
                throw new \Exception('Unhandled options for delete account - ' . $options);
            }
        }

        // Delete product listing variants
        if ($deleteListingVariants) {
            if ($productListing->listing_variants->count()) {
                $productListing->listing_variants()->delete();
            }
        }

        // Delete product listing
        $productListing->delete();

        return true;
    }

    /**
     * Returns whether the product can be created for the integration
     * This normally should check locally if all the attributes are set and is valid
     *
     * @param Product $product
     *
     * @return array|bool
     * @throws \Exception
     */
    public function canCreate(Product $product)
    {
        /* can be define by child in individual adapter */
        if (!isset($this->rules)) {
            $this->rules = [
                'name.value' => 'required'
            ];
            $this->errors = [];
        }
        if (!isset($this->variant_rules)) {
            $this->variant_rules = [];
        }

        $account = $this->account;
        // pre-load required relation data
        $this->preLoadProductData($product);
        $this->attributes = $product->attributes()->where('product_variant_id', null)
            ->where(function (Builder $query) use ($account, $product) {
                $query->whereProductId($product->id)->whereRegionId($account->region_id)->whereIntegrationId($account->integration_id);
            })
            ->orderBy('integration_id', 'asc')
            ->orderBy('region_id', 'asc')
            ->get()->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });

        /*
         * Need to retrieve attribute from product table and merge with product attributes
         * On create product page, it won't store name, brand in product attributes table
         * */
        $productAttributes = [];
        $columnNames = ['name', 'brand', 'short_description', 'html_description'];
        foreach ($columnNames as $columnName) {
            $productAttributes[$columnName]['value'] = $product[$columnName];
        }
        $this->attributes = collect($productAttributes)->merge($this->attributes);

        /* basic validation */
        $validator = Validator::make($this->attributes->toArray(), $this->rules);
        $this->errors = $validator->errors()->all();

        /* Options validation */
        $this->productOptions = (isset($this->attributes['options'])) ? json_decode($this->attributes['options']->value, true) : $product->options;
        // Get options level by integration
        $this->optionsLevels = ($this->account->integration->features[$this->account->region_id]['products']['options_level']) ?? null;

        foreach ($product->variants as $variant) {
            $this->variant_attributes = $variant->attributes()
                ->where(function (Builder $query) use ($account, $product, $variant) {
                    $query->whereProductId($product->id)->whereProductVariantId($variant->id)->whereRegionId($account->region_id)->whereIntegrationId($account->integration_id);
                })
                ->orderBy('integration_id', 'asc')
                ->orderBy('region_id', 'asc')
                ->get()->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });
            $variantAttributes = [];
            $columnNames = ['weight', 'weight_unit', 'length', 'width', 'height'];
            foreach ($columnNames as $columnName) {
                $variantAttributes[$columnName]['value'] = $product[$columnName];
            }
            $this->variant_attributes = collect($variantAttributes)->merge($this->variant_attributes);

            /* basic validation for variant */
            $validator = Validator::make($this->variant_attributes->toArray(), $this->variant_rules);
            $this->errors = array_merge($this->errors, $validator->errors()->all());

            /* Options validation */
            if (!empty($this->productOptions)) {
                // Change options key
                $this->productOptions = array_combine(range(1, count($this->productOptions)), array_values($this->productOptions));

                for ($i = 1; $i <= $this->optionsLevels; $i++) {
                    // If there is product options, make sure user fill up the variant option as well.
                    if (isset($this->productOptions[$i]) && !empty($this->productOptions[$i])) {
                        // If variant attributes does not have option, then retrieve it from product_variants table
                        $optionAttribute = ($variant->attributes->where('name', 'option_'.$i)->where('integration_id', $this->account->integration_id)->where('region_id', $this->account->region_id)->first()->value) ?? $variant->{'option_'.$i};

                        if (!$optionAttribute || empty($optionAttribute) || is_null($optionAttribute)) {
                            $this->errors[] = 'Please fill up option '.$i.' accordingly';
                        }
                    }
                }
            }
        }

        // validate integration category's attributes
        if ($this->account->integration->hasFeature($this->account->region_id, ['products', 'import_categories'])) {
            $integrationCategory = null;
            // selected different integration category, which is not mapped
            if (isset($this->attributes['integration_category_id'])) {
                $integrationCategory = IntegrationCategory::where([
                    'id' => $this->attributes['integration_category_id']['value'],
                    'integration_id' => $this->account->integration_id,
                    'region_id' => $this->account->region_id,
                ])->first();

                // Check if product attributes, contains all the integration category attributes
                if ($integrationCategory) {
                    $categoryAttributes = $integrationCategory->attributes->toArray();
                    $integrationAttributes = collect(array_merge($categoryAttributes, $this->retrieveAttributes()));

                    // Get all attributes with variant together
                    $attributes = $product->attributes()
                    ->where(function (Builder $query) use ($account, $product) {
                        $query->whereProductId($product->id)->whereRegionId($account->region_id)->whereIntegrationId($account->integration_id);
                    })
                    ->get()->mapWithKeys(function ($item) {
                        return [$item['name'] => $item];
                    })->toArray();


                    if ($this->account->integration_id == Integration::LAZADA) {
                        $integrationAttributes = $integrationAttributes->where('is_sale_prop', '!=', '1');
                    }
                    $rules = $integrationAttributes->mapWithKeys(function ($item) {
                        $rules = [];
                        if ($item['required']) {
                            $rules[] = 'required';
                        }
                        if (!empty($item['data']) && $item['type'] !== CategoryAttributeType::TEXT()->getValue() && $item['type']  !== CategoryAttributeType::RICH_TEXT()->getValue()) {
                            $data = $item['data'];
                            /*
                             * Determine data is multi dimension array
                             * Currently there is two diff type of data format
                             * Shopee - ["No Brand","Taiwan Collection", "MY Collection".....]
                             * Lazada - [{"name": "50 Cent"},{"name": "A & E Kirk"}.......] (This will me multi dimension array)
                             **/
                            if (count($data) != count($data, COUNT_RECURSIVE)) {
                                $array = [];
                                foreach ($data as $value) {
                                    // 'Value' is for qoo10 format, 'Name' is for Lazada format
                                    $array[] = array_key_exists('value', $value) ? $value['value'] : ($value['name'] ?? $value);
                                }
                                $data = $array;
                            }
                            $rules[] = Rule::in($data);
                        }

                        // If category attribute type is multi select then it should add * to treat the value as array and do the checking
                        if ($item['type'] === CategoryAttributeType::MULTI_SELECT()->getValue() || $item['type'] === CategoryAttributeType::MULTI_ENUM()->getValue()) {
                            return [$item['name'].'.value.*' => $rules];
                        } else {
                            return [$item['name'].'.value' => $rules];
                        }
                    })->toArray();

                    /*
                     * For lazada/Qoo10 some integration attribute we will store the value in same format
                     * Lazada - [{"name": "50 Cent"},{"name": "A & E Kirk"}.......]
                     * Qoo10 - [{"value": "50 Cent"},{"value": "A & E Kirk"}.......]
                     * So we need to convert the value into array and pass it to rules to do checking
                     * */
                    foreach ($attributes as $attributeName => $attribute) {
                        if (isset($attribute['value']) && $attribute['value'] === '[]') {
                            $attributes[$attributeName]['value'] = '';
                        }

                        $attributeValue = json_decode($attribute['value'], true);
                        // skip associative array
                        if (is_array($attributeValue) && !is_associative($attributeValue) && count($attributeValue) != count($attributeValue, COUNT_RECURSIVE)) {
                            $array = [];
                            foreach ($attributeValue as $value) {
                                // 'Value' is for qoo10 format, 'Name' is for Lazada format
                                if(is_array($value)) {
                                    $array[] = array_key_exists('value', $value) ? $value['value'] : ($value['name'] ?? $value);
                                }
                                else {
                                    // in case $value is string
                                    $array[] = $value;
                                }
                            }
                            $attributes[$attributeName]['value'] = $array;
                        }
                    }

                    // If is shopee skip the weight integration category attribute validation checking
                    if ($this->account->integration_id === Integration::SHOPEE) {
                        if (isset($attributes['weight'])) {
                            unset($attributes['weight']);
                        }
                    }

                    $validator = Validator::make($attributes, $rules);
                    $this->errors = array_merge($this->errors, $validator->errors()->all());
                } else {
                    $message = 'Unable to find integration category ID "'.$this->attributes['integration_category_id']['value'].'" for product from '.Integration::INTEGRATIONS[$this->account->integration_id].' '.Region::REGIONS[$this->account->region_id].' ('.$this->account->name.'). Please inform the system admin about this issue.';
                    event(new NewProductAlert($product, $message, ProductAlertType::WARNING()));
                    $this->errors[] = 'Selected integration category not found, please contact support';
                }
            } else {
                $this->errors[] = 'Please make sure there is integration category selected else please contact support';
            }
        }

        if (count($this->errors) > 0) {
            return $this->respondWithError($this->errors);
        } else {
            return $this->respond(null);
        }
    }

    /**
     * The function to check and create the product
     *
     * @param TransformedProduct $product
     * @param array $config
     * @return Product|null
     * @throws \Exception
     */
    public function handleProduct(TransformedProduct $product, $config = ['update' => false, 'new' => false, 'bundle' => false]) {
        if (property_exists($product, 'variants')) {
            if (!isset($product->variants)) {
                return;
            }
        }
        // If it's a new product, check if they have reached the product limit, if yes, we need to set it to false.
        if ($config['new']) {
            if ($this->reachedSubscriptionLimit($product)) {
                \Log::error('Subscription Limit Reached.');
                $config['new'] = false;
            }
        }

        $this->handledProducts[] = $product->listing->identifiers[ProductIdentifier::EXTERNAL_ID()->getValue()];
//        $createdProduct = null;
//        DB::transaction(function() use ($product, $config, &$createdProduct) {
            $createdProduct = $product->createProduct($this->account, $config);
//        });

        // used to debug duplicate options bug
        if (!is_null($createdProduct) && count(array_count_values($createdProduct->options)) !== count($createdProduct->options)) {
            set_log_extra('config', $config);
            set_log_extra('product', $createdProduct);
            \Log::error('Duplicate options detected.');
            $createdProduct->options = array_unique($createdProduct->options);
            $createdProduct->save();
        }
        return $createdProduct;
    }

    /**
     * Pre-load required relation data (currently only used for create product)
     *
     * @param Product $product
     */
    public function preLoadProductData(Product &$product)
    {
        $integrationId = $this->account->integration_id;
        $regionId = $this->account->region_id;
        $product->load([
            'category',
            'category.integrationCategories'=> function ($query) use ($integrationId) {
                /** @var IntegrationCategory $query */
                $query->where('integration_id', $integrationId);
            },
            'prices' => function ($query) use ($integrationId, $regionId) {
                /** @var ProductPrice $query */
                $query->where(function($subQuery) use ($integrationId, $regionId) {
                    /** @var ProductPrice $subQuery */
                    $subQuery->where(function($query) use ($integrationId, $regionId) {
                        $query->where('integration_id', $integrationId)->where('region_id', $regionId);
                    })->orWhereNull('integration_id');
                })->orderBy('type')->orderBy('integration_id');
            },
            'allImages' => function ($query) use ($integrationId) {
                /** @var ProductImage $query */
                $query->where(function($subQuery) use ($integrationId) {
                    /** @var ProductImage $subQuery */
                    $subQuery->where('integration_id', $integrationId);
                        //->orWhereNull('integration_id'); // temporary try remove this, if images got bug, enable it agn
                })->orderBy('integration_id', 'DESC')->orderBy('position');
            },
            'images'=> function ($query) use ($integrationId, $regionId) {
                    $query->where('integration_id', $integrationId)
                        ->where('region_id', $regionId)
                        ->whereNull('product_variant_id')
                        ->whereNull('product_listing_id');
            },
            'attributes' => function ($query) use ($integrationId, $regionId) {
                /** @var ProductAttribute $query */
                $query->where('integration_id', $integrationId)->where('region_id', $regionId)->whereNull('product_listing_id');
            },
            'variants',
            'variants.inventory',
            'variants.prices' => function ($query) use ($integrationId, $regionId) {
                /** @var ProductPrice $query */
                $query->where(function($subQuery) use ($integrationId, $regionId) {
                    /** @var ProductPrice $subQuery */
                    $subQuery->where(function($query) use ($integrationId, $regionId) {
                        $query->where('integration_id', $integrationId)->where('region_id', $regionId);
                    })->orWhereNull('integration_id');
                })->orderBy('type')->orderBy('integration_id');
            },
            'variants.allImages' => function ($query) use ($integrationId) {
                /** @var ProductImage $query */
                $query->where(function($subQuery) use ($integrationId) {
                    /** @var ProductImage $subQuery */
                    $subQuery->where('integration_id', $integrationId);
//                        ->orWhereNull('integration_id'); // temporary try remove this, if images got bug, enable it agn
                })->orderBy('integration_id', 'DESC')->orderBy('position');
            },
            'variants.attributes' => function ($query) use ($integrationId, $regionId) {
                /** @var ProductAttribute $query */
                $query->where('integration_id', $integrationId)->where('region_id', $regionId)->whereNull('product_listing_id');
            },
        ]);
    }


    /**
     * Retrieves all the transformed categories for the integration
     *
     * @return mixed
     * @throws \Exception
     */
    public function retrieveCategories()
    {
        set_log_extra('integration', $this->account->integration);
        throw new \Exception('Integration does not support retrieving categories.');
    }

    /**
     * Retrieves all the attributes for the IntegrationCategory
     *
     * @param IntegrationCategory $category
     *
     * @return mixed
     * @throws \Exception
     */
    public function retrieveCategoryAttribute(IntegrationCategory $category)
    {
        set_log_extra('integration', $this->account->integration);
        throw new \Exception('Integration does not support retrieving category attributes.');
    }

    /**
     * Check allowed sku limit based on subscribed plan
     *
     * @param $new
     * @param $product
     * @return boolean
     */
    private function reachedSubscriptionLimit($product)
    {
        return !SubscriptionHelper::checkSkuLimit($this->account->shop, $product);
    }

}
