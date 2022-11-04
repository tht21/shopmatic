<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\TicketReply
 *
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property string $message
 * @property int $status
 * @property int|null $staff_only
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TicketAttachment[] $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-write mixed $raw
 * @property-read \App\Models\Ticket $ticket
 * @property-read \App\Models\User $user
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TicketReply onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply whereStaffOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketReply whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TicketReply withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TicketReply withoutTrashed()
 * @mixin \Eloquent
 */
class TicketReply extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = ['ticket_id', 'message', 'user_id', 'staff_only', 'status'];


    /**
     * Returns the user for the reply
     *
     * @return mixed
     * @throws \Exception
     */
    public function getUser()
    {
        return User::where('id', $this->user_id)->first();
    }

    /**
     * Checks to see if the user is a staff
     *
     * @return bool
     * @throws \Exception
     */
    public function isStaff()
    {
        return Auth::user()->canAccessAdmin();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationship Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Relationship method for accessing the ticket's attachment(s)
     *
     * @return mixed
     */
    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_reply_id', 'id');
    }

    /**
     * Relationship method for accessing the ticket
     *
     * @return mixed
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Relationship method to get reply user
     *
     * @return mixed
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
