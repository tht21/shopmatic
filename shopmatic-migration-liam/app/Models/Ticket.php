<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\Ticket
 *
 * @property int $id
 * @property string $case_id
 * @property string $subject
 * @property string|null $description
 * @property int $shop_id
 * @property int $user_id
 * @property int $ticket_categories_id
 * @property int $status
 * @property int $priority
 * @property mixed|null $tags
 * @property int|null $related_id
 * @property string|null $related_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TicketUser[] $assignUsers
 * @property-read int|null $assign_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TicketAttachment[] $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read \App\Models\TicketCategory $category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TicketReply[] $replies
 * @property-read int|null $replies_count
 * @property-write mixed $raw
 * @property-read \App\Models\Shop $shop
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TicketTrails[] $trails
 * @property-read int|null $trails_count
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Ticket onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereCaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereRelatedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereRelatedType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereTicketCategoriesId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ticket whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Ticket withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Ticket withoutTrashed()
 * @mixin \Eloquent
 */
class Ticket extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable, SoftDeletes;

    const STATUS_OPEN = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_ANSWERED = 2;
    const STATUS_CUSTOMER_REPLIED = 3;
    const STATUS_CLOSED = 4;

    const PRIORITY_DEFAULT = 0;
    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_CRITICAL = 4;

    const STATUS_ARRAY = [
        self::STATUS_OPEN               => 'Open',
        self::STATUS_IN_PROGRESS        => 'In Progress',
        self::STATUS_ANSWERED           => 'Answered',
        self::STATUS_CUSTOMER_REPLIED   => 'Customer Replied',
        self::STATUS_CLOSED             => 'Closed'
    ];

    const PRIORITY_ARRAY = [
        self::PRIORITY_DEFAULT             => 'Default',
        self::PRIORITY_LOW                 => 'Low',
        self::PRIORITY_MEDIUM              => 'Medium',
        self::PRIORITY_HIGH                => 'High',
        self::PRIORITY_CRITICAL            => 'Critical'
    ];

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = ['subject', 'description', 'ticket_categories_id', 'related_id', 'related_type'];

    /**
     * The attributes that are hidden
     *
     * @var array
     */
    protected $hidden = ['shop_id', 'deleted_at'];

    /**
     * The attributes that are guarded
     *
     * @var array
     */
    protected $guarded = ['user_id', 'case_id', 'status'];


    /**
     * Returns the text representation of the status
     *
     * @return string
     */
    public function getStatus()
    {
        return __(self::STATUS_ARRAY[$this->status]);
    }

    /**
     * Returns the CSS color of the status
     *
     * @return string
     */

    public function getCssStatusColor()
    {
        switch ($this->status) {
            case self::STATUS_ANSWERED:
            case self::STATUS_OPEN:
                return 'green';
            case self::STATUS_CUSTOMER_REPLIED:
            case self::STATUS_IN_PROGRESS:
                return 'yellow';
            case self::STATUS_CLOSED:
                return 'black';
        }
    }

    /**
     * Returns the text representation of the priority
     *
     * @return string
     */

    public function getPriority()
    {
        return __(self::PRIORITY_ARRAY[$this->priority]);
    }

    /**
     * Returns the CSS color of the priority
     *
     * @return string
     */

    public function getCssPriorityColor()
    {
        switch ($this->priority) {
            case self::PRIORITY_DEFAULT:
            case self::PRIORITY_LOW:
                return 'green';
            case self::PRIORITY_MEDIUM:
            case self::PRIORITY_HIGH:
                return 'yellow';
            case self::PRIORITY_CRITICAL:
                return 'red';
        }
    }

    const LETTERS = 'BCDFGHJKLMNPQRSTVWXYZ';
    const ALPHANUMERIC = '0123456789BCDFGHJKLMNPQRSTVWXYZ0123456789';

    /**
     * Returns LLL-AAAAAAA
     *
     * @return string
     */
    public static function getCaseId()
    {
        $result = '';

        for ($i = 0; $i < 3; $i++)
            $result .= self::LETTERS[mt_rand(0, 20)];

        $result .= '-';


        for ($i = 0; $i < 6; $i++)
            $result .= self::ALPHANUMERIC[mt_rand(0, 40)];

        if (self::where('case_id', $result)->first()) {
            return self::getCaseId();
        }

        return $result;
    }


    /**
     * Retrieves the default storage path for the case file
     *
     * @return string
     */
    public function getStoragePath()
    {
        return 'tickets/' . $this->case_id . '/';
    }

    /**
     * Returns the user
     *
     * @return \Illuminate\Database\Eloquent\Builder|Model|object
     */
    public function getUser()
    {
        return User::where('id', $this->user_id)->first();
    }

    /**
     * Replaces the route key used in the URL
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'case_id';
    }

    /**
     * @param TicketReply $reply
     */
    public function notifyUsers(TicketReply $reply)
    {
        //TODO: When there are multiple users, notify all
    }

    /*
    |--------------------------------------------------------------------------
    | Relationship Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Relationship method for accessing the replies
     *
     * @return mixed
     */
    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id')->oldest();
    }

    /**
     * Relationship method for accessing the shop
     *
     * @return mixed
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    /**
     * Relationship method for accessing the attachments
     *
     * @return mixed
     */
    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id')->whereNull('ticket_reply_id');
    }

    /**
     * Relationship method for accessing the user
     *
     * @return mixed
     */

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Relationship method for accessing the seller
     *
     * @return mixed
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Relationship method for assigned user
     *
     * @return mixed
     */
    public function assignUsers()
    {
        return $this->hasMany(TicketUser::class,'ticket_id', 'id');
    }

    /**
     * Relationship method for accessing ticket category
     *
     * @return mixed
     */
    public function category()
    {
        return $this->hasOne(TicketCategory::class, 'id', 'ticket_categories_id');
    }

    public function trails()
    {
        return $this->hasMany(TicketTrails::class, 'ticket_id', 'id');
    }
}
