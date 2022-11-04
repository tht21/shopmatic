<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TicketUser
 *
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property int|null $read
 * @property int|null $write
 * @property int|null $update
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-write mixed $raw
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TicketTrails[] $trails
 * @property-read int|null $trails_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser whereUpdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketUser whereWrite($value)
 * @mixin \Eloquent
 */
class TicketUser extends Model
{
    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'ticket_id', 'read', 'write', 'update'];

    public function trails()
    {
        return $this->hasMany(TicketTrails::class, 'user_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
