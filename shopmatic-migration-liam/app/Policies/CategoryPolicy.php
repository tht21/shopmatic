<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Calls this before any of the other functions
     *
     * @param User $user
     * @param $ability
     *
     * @return bool
     */
    public function before(User $user, $ability)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the category
     *
     * @param User $user
     * @return mixed
     */
    public function index(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the category.
     *
     * @param User $user
     * @param Category $category
     * @return mixed
     */
    public function view(User $user, Category $category)
    {
        return true;
    }

    /**
     * Determine whether the user can create category.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can update the category.
     *
     * @param User $user
     * @param Category $category
     * @return mixed
     */
    public function update(User $user, Category $category)
    {
        return false;
    }

    /**
     * Determine whether the user can delete the category.
     *
     * @param User $user
     * @param Category $category
     * @return mixed
     */
    public function delete(User $user, Category $category)
    {
        return false;
    }

    /**
     * Determine whether the user can restore the category.
     *
     * @param User $user
     * @param Category $category
     * @return mixed
     */
    public function restore(User $user, Category $category)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the category.
     *
     * @param User $user
     * @param Category $category
     * @return mixed
     */
    public function forceDelete(User $user, Category $category)
    {
        return false;
    }
}
