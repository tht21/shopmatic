<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ArticleTag;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticleTagPolicy
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
     * Determine whether the user can view the article tag.
     *
     * @param User $user
     * @param ArticleTag $articleTag
     * @return mixed
     */
    public function view(User $user, ArticleTag $articleTag)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can create article tags.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the article tag.
     *
     * @param User $user
     * @param ArticleTag $articleTag
     * @return mixed
     */
    public function update(User $user, ArticleTag $articleTag)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the article tag.
     *
     * @param User $user
     * @param ArticleTag $articleTag
     * @return mixed
     */
    public function delete(User $user, ArticleTag $articleTag)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the article tag.
     *
     * @param User $user
     * @param ArticleTag $articleTag
     * @return mixed
     */
    public function restore(User $user, ArticleTag $articleTag)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the article tag.
     *
     * @param User $user
     * @param ArticleTag $articleTag
     * @return mixed
     */
    public function forceDelete(User $user, ArticleTag $articleTag)
    {
        return false;
    }
}
