<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\TicketAttachment
 *
 * @property int $id
 * @property string $title
 * @property int $ticket_id
 * @property int|null $ticket_reply_id
 * @property string $file_url
 * @property string $file_type
 * @property int $file_size_in_kb
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-write mixed $raw
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TicketAttachment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment whereFileSizeInKb($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment whereFileUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment whereTicketReplyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TicketAttachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TicketAttachment withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TicketAttachment withoutTrashed()
 * @mixin \Eloquent
 */
class TicketAttachment extends Model
{
    use SoftDeletes;

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = ['title', 'user_id', 'ticket_id', 'ticket_reply_id', 'file_url'];
}
