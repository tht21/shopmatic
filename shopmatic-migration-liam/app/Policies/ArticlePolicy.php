<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Article;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticlePolicy
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
     * Determine whether the user can view the article.
     *
     * @param User $user
     * @param Article $article
     * @return mixed
     */
    public function view(User $user, Article $article)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can create articles.
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
     * Determine whether the user can update the article.
     *
     * @param User $user
     * @param Article $article
     * @return mixed
     */
    public function update(User $user, Article $article)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the article.
     *
     * @param User $user
     * @param Article $article
     * @return mixed
     */
    public function delete(User $user, Article $article)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the article.
     *
     * @param User $user
     * @param Article $article
     * @return mixed
     */
    public function restore(User $user, Article $article)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the article.
     *
     * @param User $user
     * @param Article $article
     * @return mixed
     */
    public function forceDelete(User $user, Article $article)
    {
        return false;
    }
}
