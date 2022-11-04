<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ArticleCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticleCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the = article category.
     *
     * @param User $user
     * @param ArticleCategory $ArticleCategory
     * @return mixed
     */
    public function view(User $user, ArticleCategory $ArticleCategory)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can create = article categories.
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
     * Determine whether the user can update the = article category.
     *
     * @param User $user
     * @param ArticleCategory $ArticleCategory
     * @return mixed
     */
    public function update(User $user, ArticleCategory $ArticleCategory)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the = article category.
     *
     * @param User $user
     * @param ArticleCategory $ArticleCategory
     * @return mixed
     */
    public function delete(User $user, ArticleCategory $ArticleCategory)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the = article category.
     *
     * @param User $user
     * @param ArticleCategory $ArticleCategory
     * @return mixed
     */
    public function restore(User $user, ArticleCategory $ArticleCategory)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the = article category.
     *
     * @param User $user
     * @param ArticleCategory $ArticleCategory
     * @return mixed
     */
    public function forceDelete(User $user, ArticleCategory $ArticleCategory)
    {
        return false;
    }
}
