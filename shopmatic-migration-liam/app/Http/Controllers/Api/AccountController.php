<?php

namespace App\Http\Controllers\Api;

use App\Constants\AccountStatus;
use App\Constants\AuthenticationType;
use App\Constants\JobStatus;
use App\Factories\ClientFactory;
use App\Factories\WebhookAdapterFactory;
use App\Integrations\AbstractClient;
use App\Jobs\DeleteAccountJob;
use App\Models\Account;
use App\Models\DeleteAccountTask;
use App\Models\Integration;
use App\Models\Region;
use App\Models\Shop;
use App\Utilities\SubscriptionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Jobs\SyncInventory;

class AccountController extends Controller
{

    /**
     * Show the accounts index
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('index', Account::class);
        /** @var Shop $shop */
        if (Auth::user()->canAccessAdmin() && !empty($request->input('shop_id'))) {
            $shop = Shop::find($request->input('shop_id'));
        } else {
            $shop = $request->session()->get('shop');
            if (empty($shop)) {
                return $this->respondBadRequestError('There is no shop selected');
            }
        }

        // will always cap the limit under DEFAULT_MAX_LIMIT
        $limit = min(intval($request->input('limit', 10)), DEFAULT_MAX_LIMIT);

        // default auto append integration and region
        $with = array_merge(['integration', 'region'], (array)$request->input('with', []));
        $feature = $request->get('feature');
        $type = $request->get('type');
        $accounts = $shop->accounts()->with($with);
        //TODO: Filter
        $status = strtoupper($request->input('status'));
        if (!empty($status)) {
            if (AccountStatus::isValidKey($status)) {
                $accounts->where('status', AccountStatus::searchKey($status));
            } else {
                return $this->respondBadRequestError('Invalid status.');
            }
        }

        $accounts = $accounts->paginate($limit);

        // append extra account attributes
        if ($append = $request->input('append')) {
            /** @var Account $account */
            foreach ($accounts as $account) {
                $account->append($append);
            }
        }

        // Filter for accounts that have import account category
        if ($request->get('feature') === 'account_categories') {
            /** @var Collection $accounts */
            $accounts = $accounts->filter(function (Account $account) {
                return $account->hasFeature(['products', 'import_account_categories']);
            })->values()->all();
        } else if (!empty($feature)) {
            /** @var Collection $accounts */
            $accounts = $accounts->filter(function (Account $account) use ($feature) {
                return $account->hasFeature($feature);
            })->values()->all();
        }

        return $this->respondPagination($request, $accounts);
    }

    /**
     * Show the account
     *
     * @param Request $request
     *
     * @param Account $account
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function show(Request $request, Account $account)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        return $this->respond($account);
    }

    /**
     * Store the account
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @throws \Exception
     */
    public function store(Request $request)
    {
        $this->authorize('create', Account::class);
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        /* subscription checking */
        if (!SubscriptionHelper::checkAccountLimit()) {
            return $this->respondBadRequestError('You have reached your plan allowed integration\'s account limit. Please upgrade your plan');
        }

        $input = $request->input();

        // Validation of regions and integration
        $validator = Validator::make($input, [
            'region' => [
                'required',
                Rule::in(array_keys(Region::REGIONS)),
            ],
            'integration' => [
                'required',
                Rule::in(array_keys(Integration::INTEGRATIONS)),
            ]
        ]);
        if ($validator->fails()) {
            return $this->showValidationError($validator);
        }

        /** @var Integration $integration */
        $integration = Integration::find($input['integration']);

        $authType = $integration->getFeature($input['region'], ['authentication', 'type']);

        if (
            $authType != AuthenticationType::HYBRID()->getValue() &&
            $authType != AuthenticationType::FIELDS()->getValue()
        ) {

            return $this->respondBadRequestError('This integration does not support manual authentication.');
        }

        /** @var AbstractClient $client */
        $client = ClientFactory::createStatic($integration->name);

        // This is for new auth type to return back redirect url
        if ($authType == AuthenticationType::HYBRID()->getValue()) {
            try {
                return $this->respond(['redirect_url' => $client::handleManualAuth($input)]);
            } catch (\Exception $exception) {
                if ($exception->getMessage()) {
                    return $this->respondBadRequestError($exception->getMessage());
                }
                throw $exception;
            }
        }

        // dont throw error 500 if it is account duplicate error
        try {
            $account = $client::handleManualAuth($input);
        } catch (\Exception $exception) {
            if ($exception->getMessage() === 'This account has been added by other shop.') {
                return $this->respondBadRequestError($exception->getMessage());
            }
            throw $exception;
        }

        if ($account) {
            $client = ClientFactory::create($account);
            if (!$client->isCredentialsValid(true)) {

                //This is to force delete it so when the user creates it again, it'll be a new record.
                $account->forceDelete();
                return $this->respondBadRequestError('We are unable to log you in using those credentials.');
            }
        } else {
            return $this->respondBadRequestError('We are unable to create an account for that integration.');
        }
        // All the validations and creations are successful.

        return $this->respondCreated(['account' => $account]);
    }

    /**
     * @param Request $request
     * @param Account $account
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Account $account)
    {
        $this->authorize('update', $account);
        //$mode = $request->get('mode', null);
        $input = $request->all();

        return $this->respondWithMessage(['account' => $account], 'Setting updates successfully');
    }

    /**
     * Update account's settings
     *
     * @param Request $request
     * @param Account $account
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateSettings(Request $request, Account $account)
    {
        $this->authorize('update', $account);
        $input = $request->all();

        if ($defaultSettings = $account->integration->features[$account->region_id]["default_settings"]) {
            $settings = $this->generateSettingFormat($defaultSettings, $input);
            if (isset($settings['error']) && $settings['error']) {
                return $this->respondWithError(implode(', ', $settings['messages']));
            }
        }
        $account->settings = $settings;
        $account->save();

        if ($settings["products"]["automatic_inventory_sync"] === true) {
            $shop = Shop::find($account->shop_id);
            $listings = $shop->listings()->whereHas('variant', function ($query) {
                $query->whereHas('inventory', function ($query) {
                    $query->whereColumn('product_listings.stock', '!=', 'product_inventories.stock');
                });
            })->where('account_id', $account->id)->get();
            foreach ($listings as $key => $value) {
                if (($value->stock == 0 && $value->variant->inventory->stock < 0) || ($value->stock == $value->variant->inventory->stock)) {
                    continue;
                }

                SyncInventory::dispatchNow($value->variant->inventory, true, true);
            }
        }
        return $this->respondWithMessage(['account' => $account], 'Setting updates successfully');
    }

    /**
     * Delete account
     *
     * @param Request $request
     * @param Account $account
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, Account $account)
    {
        $this->authorize('delete', $account);

        $task = DeleteAccountTask::where([
            'account_id' => $account->id
        ])->where(function ($query) {
            $query->where('status', JobStatus::PENDING())
                ->orWhere('status', JobStatus::PROCESSING());
        })->get();

        // Only retrieve the action is delete_account
        $task = $task->filter(function ($value) {
            return isset($value['settings']['action']) && $value['settings']['action'] === 'delete_account';
        });

        if (count($task) > 0) {
            return $this->respondBadRequestError('You have already queued to delete for this account! Please wait for it to finish before attempting again.');
        }

        $task = DeleteAccountTask::create([
            'shop_id' => $account->shop_id,
            'user_id' => auth()->user()->id,
            'account_id' => $account->id,
            'settings' => $request->input()
        ]);

        # Added to run job chain
        DeleteAccountJob::dispatch($task->fresh());

        return $this->respondWithMessage(null, 'Successfully queued delete of account.');
    }

    public function generateSettingFormat($defaultSettings, $input)
    {
        $errors = [];
        $accountSettings = [];
        foreach ($defaultSettings as $settingGroupName => $settings) {
            foreach ($settings as $key => $setting) {
                // Convert to boolean if is checkbox
                if ($setting['type'] === 'checkbox') {
                    $input[$setting['name']] = (isset($input[$setting['name']]) && $input[$setting['name']] === 'on') ? true : false;
                }

                // Check whether is required for radio/text
                if ($setting['required'] && ($setting['type'] === 'radio' || $setting['type'] === 'text')) {
                    if (!isset($input[$setting['name']]) || empty($input[$setting['name']])) {
                        $errors[] = $setting['label'] . ' is required';
                    }
                }

                // Radio data must be valid
                if ($setting['type'] === 'radio' && isset($input[$setting['name']])) {
                    if (!array_key_exists($input[$setting['name']], $setting['data'])) {
                        $errors[] = $setting['label'] . ' is invalid';
                    }
                }
                $accountSettings[$settingGroupName][$key] = ($input[$setting['name']]) ?? null;
            }
        }
        return (count($errors) > 0) ? ['error' => true, 'messages' => $errors] : $accountSettings;
    }

    /**
     * Webhook at an account level.
     *
     * @param Request $request
     * @param Account $account
     * @return mixed
     */
    public function webhook(Request $request, Account $account)
    {
        $adapter = null;
        try {
            $adapter = WebhookAdapterFactory::createFromAccount($account);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }
        return $adapter->handle($request);
    }

    /**
     * Returns the list of API calls that needs to be called
     *
     * @param Request $request
     * @param Account $account
     * @return mixed
     */
    public function setupIndex(Account $account)
    {
        $client = null;
        try {
            $client = $account->getClient();
            return $this->respond($client->postAccountCreation);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }
    }


    /**
     * Performs the action for the Client.
     * This is used only for the setup scripts needed for the Client
     *
     * @param Request $request
     * @param Account $account
     * @param $action
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function action(Request $request, Account $account, $action)
    {
        $this->authorize('update', $account);

        /** @var AbstractClient $client */
        $client = $account->getClient();

        if (empty($client)) {
            return $this->respondNotFound();
        }

        if (!method_exists($client, $action) || !array_key_exists($action, $client->postAccountCreation)) {
            return $this->respondBadRequestError('This action is not supported');
        }

        $response = $client->{$action}($account, $request);

        if (!is_bool($response)) {
            return $this->respond($response);
        }

        return $this->respond();
    }


    public function toggleStatus(Request $request, Account $account)
    {
        $this->authorize('update', $account);

        $status = $request->input('status');

        $account->status = $status;
        $account->save();

        return $this->respondWithMessage(['account' => $account], 'Status updated successfully');
    }
}
