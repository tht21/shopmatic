<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\TicketTrails
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $ticket_id
 * @property int $action
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-write mixed $raw
 * @property-read \App\Models\Ticket $ticket
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketTrails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketTrails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketTrails query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketTrails whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketTrails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketTrails whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketTrails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketTrails whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketTrails whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketTrails whereUserId($value)
 * @mixin \Eloquent
 */
class TicketTrails extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    const TICKET_CREATE = 0;
    const TICKET_REPLY = 1;

    const TRAILS_ARRAY = [
        self::TICKET_CREATE => 'Create Ticket',
        self::TICKET_REPLY => 'Reply Ticket',
    ];

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'action', 'description'];

    /**
     * Retrieve the user of the ticket trails
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Retrieve the ticket belongs to the ticket trails
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket() {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }
}
