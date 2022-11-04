<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use Silber\Bouncer\Database\HasRolesAndAbilities;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $remember_token
 * @property string|null $phone_number
 * @property string $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $slack_username
 * @property-read \Illuminate\Database\Eloquent\Collection|\Silber\Bouncer\Database\Ability[] $abilities
 * @property-read int|null $abilities_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Client[] $clients
 * @property-read int|null $clients_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Silber\Bouncer\Database\Role[] $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Shop[] $shops
 * @property-read int|null $shops_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Token[] $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Account[] $accounts
 * @property-read int|null $accounts_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereIs($role)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereIsAll($role)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereIsNot($role)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereCardBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereCardLastFour($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereSlackUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereTrialEndsAt($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class User extends Authenticatable implements Auditable, MustVerifyEmail
{

    use SoftDeletes, HasApiTokens, \OwenIt\Auditing\Auditable, Notifiable, HasRolesAndAbilities;

    const ROLE_SUPER_ADMIN = 'superadmin';
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPPORT = 'support';
    const ROLE_DESIGN = 'design';

    const ADMIN_DASHBOARD_ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_SUPPORT,
        self::ROLE_DESIGN,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone_number', 'email_verified_at', 'slack_username'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'deleted_at', 'role'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that are added to the array
     *
     * @var array
     */
    protected $appends = [
        'role'
    ];


    /**
     * Override toArray to show certain data only if user has permission
     *
     * @return array
     */
    public function toArray()
    {
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();
            if ($user->canAccessAdmin()) {
                $this->makeVisible(['role']);
            }
        }
        return parent::toArray();
    }
    
    public function getRoleAttribute()
    {
        return implode(', ', $this->getRoles()->toArray());
    }

    /**
     * START - Helper Methods
     */

    /**
     * Changes the created_at to a human readable time
     *
     * @param $value
     *
     * @return string
     */
    public function getCreatedAtAttribute($value)
    {
        return date_time_text($value);
    }

    /**
     * Route notifications for the Slack channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForSlack($notification)
    {
        return config('combinesell.slack.webhook');
    }
    /**
     * Returns whether or not the user has admin privileges
     *
     * @return bool
     */
    public function canAccessAdmin()
    {
        foreach (self::ADMIN_DASHBOARD_ROLES as $role) {
            if ($this->isA($role) || $this->isAn($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * END - Helper Methods
     */



    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the shops the user belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'user_shop_pivot', 'user_id', 'shop_id');
    }

    public function accounts()
    {
        return $this->hasManyThrough(Account::class, Shop::class);
    }

    /**
     * END - Helper Methods
     */
}
