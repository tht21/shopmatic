<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Constants\JobStatus;
use App\Http\Requests\Api\StoreAccountCategory;
use App\Http\Requests\Api\UpdateAccountCategory;
use App\Jobs\AccountCategoryImportJob;
use App\Models\AccountCategoryImportTask;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Account $account
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, Account $account)
    {
        $this->authorize('index', AccountCategory::class);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        /** @var Category $query */
        $query = $account->categories()->with('parent')->orderBy('id', 'DESC');

        if ($request->has('is_leaf')) {
            $query = $query->whereIsLeaf($request->get('is_leaf'));
        }

        return $this->respondPagination($request, $query->paginate($limit));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreAccountCategory $request
     * @param Account $account
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function store(StoreAccountCategory $request, Account $account)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('create', [AccountCategory::class, $account, $shop]);

        // Validation
        $input = $request->validated();

        # Make sure account have account categories feature
        if (!$account->hasFeature(['products', 'import_account_categories'])) {
            return $this->respondBadRequestError('Account does not support account categories.');
        }

        # Make sure parent account category is under the account
        if (!$parentAccountCategory = $account->categories()->whereId($input['parent_id'])->whereIsLeaf(false)->first()) {
            return $this->respondBadRequestError('Invalid of parent category.');
        }

        # Make sure category not yet mapped with the account yet
        if ($account->categories()->whereCategoryId($input['category_id'])->exists()) {
            return $this->respondBadRequestError('Category already mapped with this account.');
        }

        # Call integration api to create new category
        $adapter = $account->getProductAdapter();
        $adapterCategory = $adapter->createAccCategory($input);

        // @NOTE - check if create successfully in account
        # Create new account category
        if ($adapterCategory) {
            $accountCategory = $account->categories()->create($input + [
                'external_id' => $adapterCategory->id,
            ]);

            return $this->respondCreated($accountCategory->toArray());
        }
        return $this->respondBadRequestError('Unable to create category');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateAccountCategory $request
     * @param Account $account
     * @param AccountCategory $accountCategory
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function update(UpdateAccountCategory $request, Account $account, AccountCategory $accountCategory)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('edit', AccountCategory::class);

        // Validation
        $input = $request->validated();

        # Make sure account is under shop and have account categories feature
        if (!$account->hasFeature(['products', 'import_account_categories'])) {
            return $this->respondBadRequestError('Account does not support account categories.');
        }

        # Make sure parent account category is under the account
        if (!$parentAccountCategory = $account->categories()->whereId($input['parent_id'])->where('id', '!=', $accountCategory->id)->whereIsLeaf(false)->first()) {
            return $this->respondBadRequestError('Invalid of parent category or cannot choose back own as parent.');
        }

        # Make sure category not yet mapped with the account yet
        if ($account->categories()->whereCategoryId($input['category_id'])->where('id', '!=', $accountCategory->id)->exists()) {
            return $this->respondBadRequestError('Category already mapped with this account.');
        }

        // mapping has nothing to do with adapter update
        $accountCategory->update([
            'category_id' => $input['category_id']
        ]);

        # Call integration api to create new category
        $adapter = $account->getProductAdapter();
        $adapterCategory = $adapter->updateCategory($accountCategory->external_id, $input);

        # Create new account category
        if ($adapterCategory) {
            $accountCategory->update($input + [
                'external_id' => $adapterCategory->id
            ]);

            return $this->respondCreated($accountCategory->toArray());
        }
        return $this->respondBadRequestError('Unable to update category');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Imports the categories for the account
     *
     * @param Request $request
     * @param Account $account
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function import(Request $request, Account $account)
    {
        $this->authorize('create', [AccountCategory::class, $account]);

        $task = AccountCategoryImportTask::where([
            'source_type' => get_class($account),
            'source' => $account->id,
        ])->where(function($query) {
            $query->where('status', JobStatus::PENDING())
                ->orWhere('status', JobStatus::PROCESSING());
        })->count();

        if (!empty($task)) {
            return $this->respondBadRequestError('You have already queued to import categories for this integration! Please wait for it to finish before attempting again.');
        }

        $task = AccountCategoryImportTask::create([
            'shop_id' => $account->shop_id,
            'user_id' => Auth::user()->id,
            'source_type' => get_class($account),
            'source' => $account->id,
            'settings' => $request->input()
        ]);

        AccountCategoryImportJob::dispatch($task->fresh());

        return $this->respondWithMessage(null, 'Successfully queued import of categories for account.');
    }
}
