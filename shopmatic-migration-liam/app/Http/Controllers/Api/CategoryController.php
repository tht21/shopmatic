<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Integration;
use App\Models\IntegrationCategory;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('index', Category::class);
        // id can be category id or integration category id, depends on integration id is null or not
        // if wanna get integration id, pass integration_id to here
        // will always cap the limit under DEFAULT_MAX_LIMIT
        $id = $request->input('id', null);
        $integrationId = $request->input('integration_id', null);
        $regionId = $request->input('region_id', null);
        $accountId = $request->input('account_id', null);
        $categoryId = $request->input('category_id', null);
        $search = $request->input('search', null);
        $with = $request->input('with', null);
        $export = $request->input('export', null);
        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        if (is_null($integrationId) && is_null($accountId)) {
            $query = Category::with(['integrationCategories'])->whereIsLeaf(true)->orderBy('breadcrumb', 'asc')->whereHas('integrationCategories');
        } elseif (is_null($accountId)) {
            $query = IntegrationCategory::with(['category'])->whereIntegrationId($integrationId)->whereIsLeaf(true)->where('visible', 1);

            if (!is_null($regionId)) {
                $query = $query->whereRegionId($regionId);
            }

            if (!empty($categoryId)) {
                $query->where(function (Builder $query) use ($categoryId) {
                    return $query->where('category_id', $categoryId)
                        ->orWhereHas('categories', function (Builder $query) use ($categoryId) {
                            $query->where('category_integration_category.category_id', $categoryId);
                        });
                });

            }
        } else {
            $query = AccountCategory::whereAccountId($accountId);
        }

        if (!is_null($id)) {
            $query = $query->whereId($id);
        } elseif (!is_null($search)) {
            $query = $query->where('breadcrumb', 'LIKE', '%'.$search.'%');
        }

        if (!is_null($with)) {
            $query = $query->with($with);
        }

        // query for export products
        if (!empty($export)) {
            $query = $this->exportQuery($query, $request);
        }

        return $this->respondPagination($request, $query->paginate($limit));
    }

    /**
     * Returns the listing of past import tasks and their status
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    /*public function importTasks(Request $request)
    {
        $this->authorize('create', Product::class);

        $shop = $request->session()->get('shop');

        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);
        $users = $shop->productImportTasks()->latest()->paginate($limit);
        return $this->respondPagination($request, $users);
    }*/

    /**
     * Get integration categories from cache
     *
     * @param int|string $integrationId
     * @param string $mode
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getIntegrationCategories($integrationId, $mode, $limit = 10)
    {
        $query = IntegrationCategory::select(['id', 'breadcrumb', 'category_id', 'external_id'])->where('integration_id', $integrationId);

        if ($integrationId && $mode === 'collection') {
            $integrationCategories = $query->get();
        } else if ($integrationId && $mode === 'pluck') {
            $integrationCategories = $query->get()->pluck('breadcrumb', 'id');
        } else {
            $integrationCategories = $query->paginate($limit);
        }

        return $integrationCategories;
    }

    /**
     * Get category attributes based on the give account
     *
     * @param $category Category|IntegrationCategory|AccountCategory|string
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function getAttributes($category, Request $request)
    {
        $this->authorize('index', Category::class);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        /** @var Account $account */
        if ($account = $shop->accounts()->find($request->input('account'))) {
            $account = $account->append('has_category');
        } else {
            set_log_extra('request', $request->all());
            Log::error('Account with ID: ' . $request->input('account') . ' does not belongs to ' . $shop->name . 'shop.');
            return $this->respondBadRequestError('Account with ID: ' . $request->input('account') . ' does not belongs to ' . $shop->name . 'shop.');
        }

        if (empty($account)) {
            return $this->respondBadRequestError('There is no account selected');
        }

        // pass from api call
        if (!$account->has_category) {
            // if integration that don't have any category, set it to null
            $category = null;
        } elseif (gettype($category) === 'string') {
            // detect category type, default is Category
            if ($type = $request->input('type')) {
                if ($type === 'Category') {
                    $category = Category::find($category);
                } elseif ($type === 'IntegrationCategory') {
                    $category = IntegrationCategory::find($category);
                } elseif ($type === 'AccountCategory') {
                    $category = AccountCategory::find($category);
                } else {
                    return $this->respondBadRequestError('Category type not supported');
                }
            } else {
                $category = Category::find($category)->integrationCategories()->where('integration_id', $account->integration_id)->first();
            }

        // pass by function call
        } elseif ($category instanceof Category) {
            $category = $category->integrationCategories()->where('integration_id', $account->integration_id)->first();
        } elseif ($category instanceof IntegrationCategory) {
            if (!$account->integration->categories()->where('id', $category->id)->exists()) {
                return $this->respondBadRequestError('Integration category selected not exist under selected account\'s integration');
            }
        } elseif ($category instanceof AccountCategory) {
            if (!$account->categories()->where('id', $category->id)->exists()) {
                return $this->respondBadRequestError('Account category selected not exist under selected account\'s integration');
            }

        // not supported type return error
        } else {
            return $this->respondBadRequestError('Category type not supported');
        }

        $regionId = $request->input('region_id');
        $attributes = [
            'attributes' => [],
            'logistics' => [],
            'prices' => [],
            'options' => null
        ];

        /* get constant attributes from integration */
        $attributes['attributes'] = $account->getIntegrationAttributes();

        /* check if account has imported integration categories, and category exists */
        if ($account->hasFeature(['products', 'import_categories']) && $category instanceof IntegrationCategory) {
            /* merge category attributes and constant attributes */
           $categoryAttributes = $category->getCachedAttributes()->toArray();
            if (!empty($categoryAttributes)) {
                // If in the future we need to transform attributes
//                $categoryAttributes = $account->transformAttributes($categoryAttributes);
                $attributes['attributes'] = array_merge($attributes['attributes'], $categoryAttributes);
                if ($account->integration_id === Integration::LAZADA) {
                    $lazadaAttr = [];
                    $lazadaRequiredAttr = [];
                    foreach ($attributes['attributes'] as $attribute) {
                        if (isset($attribute['is_sale_prop']) && $attribute['is_sale_prop'] == 0) {
                            $lazadaAttr[] = $attribute;
                        }
                        elseif (isset($attribute['is_sale_prop']) && $attribute['is_sale_prop'] == 1) {
                            $lazadaRequiredAttr[] = $attribute;
                        }
                    }
                    $attributes['is_sale_prop_require'] = count($lazadaRequiredAttr);
                    $attributes['recommended_attributes'] = $lazadaRequiredAttr;
                    $attributes['attributes'] = $lazadaAttr;
                }
            }
            // Fetched Categories Based on Integration Id and Region Id For Lazada
            /*if (!empty($regionId) && $account->integration_id === Integration::LAZADA && array_search('brand', array_column($attributes['attributes'], 'name'))) {
                $brandKey = array_search('brand', array_column($attributes['attributes'], 'name'));
                if (!empty($brandKey)) {
                    $brands = Brand::getCachedBrands(Integration::LAZADA,$regionId);
                    $brands = $brands->map(function ($item, $key) {
                        //return ['name' => $item['name'], 'id' => $item['external_id']];
                        return $item['name'];
                    })->toArray();
                    $attributes['attributes'][$brandKey]['data'] = $brands;
                }
            }*/
        }
        /* get dynamic logistic data from account */
        $attributes['logistics'] = $account->getLogisticsAttributes();
        /* get integration available price type from account */
        $attributes['prices'] = $account->getPriceTypes();
        /* get level of options available for the integration */
        $attributes['options'] = ($account->integration->features[$account->region_id]['products']['options_level']) ?? null;
        return $this->respond($attributes);
    }

    public function exportQuery($query, Request $request)
    {
        $export = json_decode($request->input('export'), true);
        $accountId = $export['account_id'] ?? null;
        $integrationId = $export['integration_id'] ?? null;

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        if ($accountId) {
            // Retrieve all the products category id
            $categoryIds = $shop->products()->where(function ($query) use ($accountId) {
                $query->whereDoesntHave('listings', function (Builder $q) use ($accountId) {
                    $q->whereAccountId($accountId);
                });
            })->groupBy('category_id')->pluck('category_id');

            // filter by product category
            $query = $query->whereIn('id', $categoryIds);
        }
        return $query;
    }
}
