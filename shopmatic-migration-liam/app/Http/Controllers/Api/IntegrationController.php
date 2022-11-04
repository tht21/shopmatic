<?php

namespace App\Http\Controllers\Api;

use App\Constants\AuthenticationType;
use App\Constants\IntegrationType;
use App\Factories\ClientFactory;
use App\Factories\WebhookAdapterFactory;
use App\Models\Integration;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IntegrationController extends Controller
{

    /**
     * Shows the integrations index
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {

        $this->authorize('index', Integration::class);
        $shop = $request->session()->get('shop');
        $shopId = $shop->id ?? '';
        $regions = Region::where('visibility', 1)->get();
        $integrations = Cache::remember('integrations', config('app.env') == 'production' ? 86400 : 1, function() {
            $types = IntegrationType::toArray();
            $integrations = [];
            foreach ($types as $name => $type) {
                $query = Integration::where('type', $type);
                $visibleIntegrations = $query->get()->makeVisible('features')->toArray();
                $integrations[] = [
                    'name' => ucwords($name),
                    'type' => $type,
                    'integrations' => $visibleIntegrations
                ];
            }
            return $integrations;
        });
        return $this->respond(['regions' => $regions, 'integrations' => $integrations]);
    }

    /**
     * Shows the integrations index
     *
     * @param Integration $integration
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Integration $integration)
    {

        $this->authorize('view', $integration);
        $integration = $integration->makeVisible('features');
        return $this->respond($integration->toArray());
    }


    /**
     * Returns the URL for the redirect. We need to do this as there's session variables we need to set
     *
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function getAuthorizationLink(Request $request)
    {
        $region = $request->input('region');
        $integrationId = $request->input('integration_id');
        if (empty($integrationId)) {
            return $this->respondBadRequestError('integration_id not specified.');
        }
        if (empty($region)) {
            return $this->respondBadRequestError('region not specified.');
        }

        /** @var Integration $integration */
        $integration = Integration::find($integrationId);
        if (empty($integration)) {
            return $this->respondBadRequestError('Integration invalid');
        }

        //Check if this integration and region supports OAuth
        if (!$integration->hasFeature($region, ['authentication', 'enabled'])) {
            return $this->respondBadRequestError('Integration does not support automatic authentication');
        }

        $authenticationType = $integration->getFeature($region, ['authentication', 'type']);
        if ($authenticationType != AuthenticationType::OAUTH()->getValue() &&
            $authenticationType != AuthenticationType::HYBRID()->getValue()) {
            return $this->respondBadRequestError('Integration does not support OAuth.');
        }

        $client = ClientFactory::createStatic($integration->name);

        return $this->respond(['redirect_url' => $client::getAuthorizationLink($region)]);
    }

    /**
     * Webhook at an integration level.
     * We will need to find the associated shop / account from the data directly
     *
     * @param Request $request
     * @param Integration $integration
     * @return mixed
     */
    public function webhook(Request $request, Integration $integration)
    {
        $adapter = null;
        try {
            $adapter = WebhookAdapterFactory::create($integration->name);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }
        return $adapter->handle($request);
    }
}
