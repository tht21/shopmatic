<?php

namespace App\Http\Controllers\Dashboard;

use App\Factories\ClientFactory;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Integration;
use App\Utilities\SubscriptionHelper;
use Illuminate\Http\Request;

class AccountController extends Controller
{

    /**
     * Shows the listing of all accounts
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('dashboard.accounts.index');
    }

    /**
     * Shows the create page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {
        return view('dashboard.accounts.create');
    }

    /**
     * Shows the setup page
     *
     * @param Account $account
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function setup(Account $account)
    {
        $account->load(['integration']);
        return view('dashboard.accounts.setup', compact('account'));
    }

    /**
     * Handles the redirect for integrations
     *
     * @param Request $request
     * @param string $integration The name of the integration
     * @param null $param
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function handleRedirect(Request $request, $integration, $param = null)
    {
        /* subscription checking */
        if (!SubscriptionHelper::checkAccountLimit()) {
            flash()->error('You have reached your plan allowed integration\'s account limit. Please upgrade your plan');
            return redirect(route('dashboard.accounts.index'));
        } else {
            $input = $request->input();
            $client = ClientFactory::createStatic($integration);

            // Currently only for amazon usage
            if (!is_null($param)) {
                $input['extra_param'] = $param;
            }

            // block error thrown to frontend and show error message to user
            try {
                $integration = $client::handleRedirect($input);
            } catch (\Exception $exception) {
                flash()->error($exception->getMessage());
                return redirect(route('dashboard.accounts.index'));
            }

            if ($integration) {
                flash()->success('You have successfully added your account!');
                return redirect(route('dashboard.accounts.setup', ['account' => $integration]));
            } else {
                flash()->error('There was an error adding your account. Please try again later or contact our customer support.');
                return redirect(route('dashboard.accounts.index'));
            }
        }

    }

    /**
     * Handles the redirect for reactivation
     *
     * @param Account $account
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function handleReactivation(Account $account)
    {
        $client = ClientFactory::create($account);
        $integration = $account->integration;

        //Check if this integration and region supports OAuth
        if (!$integration->hasFeature($account->region_id, ['authentication', 'enabled'])) {
            flash()->error('This integration does not support automated authentication');
            return back();
        }

        // Differentiate authorization link or manual auth
        switch ($account->integration_id) {
            case Integration::SHOPIFY:
            case Integration::AMAZON:
                $input = $this->handleManualAuthInput($account);
                try {
                    return redirect($client::handleManualAuth($input));
                } catch (\Exception $exception) {
                    if ($exception->getMessage() === 'This account has been added by other shop.') {
                        flash()->error($exception->getMessage());
                        return back();
                    }
                    throw $exception;
                }
                break;
            case Integration::QOO10_LEGACY:
                $input = $this->handleManualAuthInput($account);

                try {
                    $account = $client::handleManualAuth($input);
                } catch (\Exception $exception) {
                    if ($exception->getMessage() === 'This account has been added by other shop.') {
                        flash()->error($exception->getMessage());
                        return back();
                    }
                    throw $exception;
                }
                return back();
                break;
            default:
                return redirect($client::getAuthorizationLink($account->region_id));
        }
    }

    /**
     * Handle manual auth input for reactivation
     *
     * @param $account
     * @return array
     */
    private function handleManualAuthInput($account)
    {
        $input = [];
        switch ($account->integration_id) {
            case Integration::QOO10_LEGACY:
                $input = [
                    'region' => $account->region_id,
                    'username' => $account->credentials['username'],
                    'password' => $account->credentials['password'],
                ];
                break;
            case Integration::AMAZON:
                $input = [
                    'marketplace_id' => $account->credentials['marketplace_id'],
                ];
                break;
        }
        return $input;
    }
}
