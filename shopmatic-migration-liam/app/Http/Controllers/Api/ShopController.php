<?php

namespace App\Http\Controllers\Api;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{

    /**
     * Show the shops index
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('index', Shop::class);
        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        //get Current session's user
        $user = Auth::user();

        if ($user->canAccessAdmin() && !empty($request->input('user_id')) ) {
            $user = User::find($request->get('user_id'));
            if (empty($user)) {
                return $this->respondNotFound('User not found');
            }
            $shops = $user->shops()->paginate($limit);
            $shops->each(function ($shop) {
                $shop->makeVisible('e2e');
            });
        } else {
            $shops = $user->shops()->paginate($limit);
        }

        return $this->respondPagination($request, $shops);
    }

    /**
     * Creates the user
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Shop::class);
        $input = $request->input();
        if ( Auth::user()->canAccessAdmin() && !empty($request->input('user_id')) ) {
            $user_id = $input['user_id'] ;
            unset($input['user_id']);

            // @TODO - create StoreShop Request to validate, can refer to UpdateProduct.php and ProductController.php L242
            // Validation
            $validator = Validator::make( $input, [
                'name' => 'required|string',
                'email' => 'required|email',
                'phone_number' => 'required|string|min:7|max:15',
                'currency' => 'required_if:multi_currency,0|in:MYR,SGD',
                'e2e' => 'boolean'
            ]);
        } else {
            $user_id = Auth::user()->id ;

            // @TODO - create StoreShop Request to validate, can refer to UpdateProduct.php and ProductController.php L242
            // Validation
            $validator = Validator::make($input, [
                'name' => 'required|string',
                'email' => 'required|email',
                'phone_number' => 'required|string|min:7|max:15',
                'currency' => 'required_if:multi_currency,0|in:MYR,SGD'
            ]);
        }

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        /** @var Shop $shop */
        $shop = Shop::create($input);

        //If shop is e2e
        if (array_key_exists('e2e', $input)) {
            $shop->e2e = $input['e2e'];
            $shop->save();
        }

        // Adding the user to the shop
        $shop->users()->sync([ $user_id ], false);

        $shop = $shop->fresh();

        // Create as Stripe Customer
        $shop->createAsStripeCustomer();

        return $this->respondCreated($shop->toArray());
    }

    /**
     * Assign user to shop
     *
     * @param Request $request
     * @param $users
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function assign(Request $request, $users)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('update', $shop);

        $shop->users()->sync([$users], false);

        return $this->respond($shop->fresh('users'));
    }

    /**
     * Dismiss user from shop
     *
     * @param Request $request
     * @param $users
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function dismiss(Request $request, $users)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('update', $shop);

        $user = $shop->users()->find($users);

        $shop->users()->detach($users);

        // @NOTE - delete because right now is newly added user
        $user->delete();

        return $this->respond($shop->fresh('users'));
    }

    /**
     * Invite user to shop
     *
     * @param Request $request
     * @param $users
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function invite(Request $request)
    {

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('update', $shop);

        $emails = $request->input('emails');

        foreach ($emails as $key => $value) {
            $user = User::where('email', $value)->first();

            $shop->users()->sync([$user->id], false);
        }

        // @TODO - send email to user, and maybe should ask for confirmation to join instead of directly add

        return $this->respondWithMessage($shop->fresh('users'), 'Invited users to shop');
    }

    /**
     * Updates the shop
     *
     * @param Request $request
     * @param Shop $shop
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Shop $shop)
    {

        $this->authorize('update', $shop);
        $input = $request->input();

        if ( Auth::user()->canAccessAdmin() ) {

            // @TODO - create UpdateShop Request to validate, can refer to UpdateProduct.php and ProductController.php L242
            // Validation
            $validator = Validator::make($input, [
                'name' => 'required|string',
                'email' => 'required|email',
                'phone_number' => 'required|string|min:7|max:15',
                'logo' => 'mimes:jpeg,png',
                'e2e' => 'boolean'
            ]);
        } else {

            // @TODO - create UpdateShop Request to validate, can refer to UpdateProduct.php and ProductController.php L242
            // Validation
            $validator = Validator::make($input, [
                'name' => 'required|string',
                'email' => 'required|email',
                'phone_number' => 'required|string|min:7|max:15',
                'logo' => 'mimes:jpeg,png'
            ]);
        }

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        if($request->shop_image) {
            foreach ($request->shop_image as $file) {
                $src = uploadImageFile($file['data_url'], $shop);
                $input['logo'] = $src;
            }
        }

        //If shop is e2e
        if (array_key_exists('e2e', $input)) {
            $shop->e2e = $input['e2e'];
            $shop->save();
        }

        $shop->update($input);


        return $this->respondWithMessage([], 'Shop updated successfully.');
    }

    /**
     * Delete shop
     *
     * @param Request $request
     * @param \App\Models\Shop $shop
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, Shop $shop)
    {
        $this->authorize('delete', $shop);
        $shop->delete();
        return $this->respondWithMessage([], 'Shop deleted successfully.');
    }

    /**
     * Switch shop
     *
     * @param Request $request
     * @param \App\Models\Shop $shop
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function switch(Request $request, Shop $shop)
    {
        $this->authorize('update', $shop);
        $request->session()->put('shop', $shop);
        return $this->respondWithMessage([], 'Shop switched successfully.');
    }
}
