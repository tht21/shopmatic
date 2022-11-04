<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TicketUser
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $read
 * @property int|null $write
 * @property int|null $update
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-write mixed $raw
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\UserActivity[] $trails
 * @property-read int|null $trails_count
 * @property-read \App\Models\User $user
 * @mixin \Eloquent
 */
class UserActivity extends Model
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_activity';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ip_address',
        'user_id',
        'user_email',
        'login_timestamp',
        'logout_timestamp',
        'session_id'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
