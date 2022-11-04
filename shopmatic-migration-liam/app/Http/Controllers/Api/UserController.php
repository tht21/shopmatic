<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Notifications\NewAccountInformation;
use App\Utilities\SubscriptionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    /**
     * Show the user index
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('index', User::class);

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);
        $users = User::query();
        if ($search = $request->input('search')) {
            $users->where('email', 'LIKE', '%' . $search . '%')
                ->orWhere('name', 'LIKE', '%' . $search . '%')
                ->orWhere('id', 'LIKE', '%' . $search . '%');
        }
        $users = $users->latest()->paginate($limit);

        return $this->respondPagination($request, $users);
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
        $this->authorize('create', User::class);

        //check shop user limit if not admin
        if (!Auth::user()->canAccessAdmin()) {
            if (!SubscriptionHelper::checkUserLimit()) {
                return $this->respondWithError('Unable to create a new user, your subscription plan creation user quota has been exhausted');
            }
        }

        $input = $request->input();
        $plainPass = $input['password'] ?? '';
        if (!empty($input['email_notification']) || empty($plainPass)) {
            $plainPass = Str::random(12);
            $input['password'] = $plainPass;
        }

        // Validation
        $validator = Validator::make($input, [
            'email' => ['required', 'email', Rule::unique('users')->where(function ($query) {
                return $query->whereNull('deleted_at');
            })],
            'name' => 'required|string',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->showValidationError($validator);
        }

        /*
         * Automatic verification if created from admin side
         */
        if (Auth::user()->canAccessAdmin()) {
            $input['email_verified_at'] = now();
        }

        // Hashing password
        $input['password'] = Hash::make($input['password']);

        /** @var User $user */
        $user = User::create($input);
        if (!empty($input['email_notification']) || !empty($input['force_email_notification'])) {
            $user->notify(new NewAccountInformation($plainPass));
        }
        $user = $user->fresh();
        return $this->respondCreated($user->toArray());
    }

    /**
     * Update the user
     *
     * @param Request $request
     *
     * @param User $user
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $input = $request->input();
        // Validation
        if (Auth::user()->canAccessAdmin()) {
            $validator = Validator::make($input, [
                'name' => 'required|string',
                'password' => 'nullable|min:6|confirmed',
            ]);
        } else {
            $validator = Validator::make($input, [
                'name' => 'required|string',
                'password' => 'required_with:old_password|nullable|min:6|confirmed',
            ]);
        }

        if ($validator->fails()) {
            return $this->showValidationError($validator);
        }

        if (isset($input['old_password']) && !Hash::check($input['old_password'], $user->password)) {
            return $this->respondWithError('old password incorrect.');
        }

        if (isset($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            unset($input['password']);
        }

        $user->update($input);

        return $this->respondWithMessage([], 'User updated successfully.');
    }

    /**
     * Creates the user
     *
     * @param User $user
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', User::class);
        foreach ($user->shops as $shop) {
            if ($shop->users()->count() === 1) {
                $shop->products()->delete();
                $shop->productVariants()->delete();
                $shop->listings()->delete();
                $shop->orders()->delete();
                $shop->orderItems()->delete();
                $shop->inventories()->delete();
                $shop->inventoryTrails()->delete();
                $shop->alerts()->delete();
                $shop->contacts()->delete();
                $shop->reports()->delete();
                $shop->accounts()->delete();
            }
        }
        $user->delete();

        return $this->respond();
    }
}