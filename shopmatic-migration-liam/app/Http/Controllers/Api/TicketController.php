<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketReply;
use App\Models\TicketTrails;
use App\Models\TicketUser;
use App\Utilities\FileStorageHelper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return mixed
     * @throws AuthorizationException
     */
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorize('index', Ticket::class);

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $ticket = $ticket->newQuery();

        $searchFields = ['case_id','subject', 'description'];

        if ($request->has('filter')){
            $ticket->where(function ($query) use ($request, $searchFields) {
                $searchWildcard = '%' . $request->filter . '%';
                foreach ($searchFields as $field) {
                    $query->orWhere($field, 'LIKE', $searchWildcard);
                }
            });
        };

        if ($request->has('ticket_categories_id')){
            $ticket->where('ticket_categories_id', $request->ticket_categories_id);
        };

        $data = $ticket->with('category','user', 'assignUsers.user')
            ->where('user_id', Auth::user()->id)
            ->orderBy('id', 'desc')
            ->paginate($limit);

        return $this->respondVueTable($request, $data);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        return $this->respondNotFound();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Ticket::class);

        $shop = $request->session()->get('shop');
        if (empty($shop)){
            return $this->respondBadRequestError('There is no shop selected');
        }

        $input = $request->input();

        $validator = Validator::make($request->input(), [
            'ticket_categories_id' => 'required',
            'subject' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        $ticket = new Ticket;
        $ticket->fill($input);
        $ticket->case_id = Ticket::getCaseId();
        $ticket->shop_id = $shop->id;
        $ticket->user_id = Auth::user()->id;
        $ticket->status = Ticket::STATUS_OPEN;
        $ticket->save();

        $trail = new TicketTrails;
        $trail->user_id = Auth::user()->id;
        $trail->ticket_id = $ticket->id;
        $trail->action = TicketTrails::TICKET_CREATE;
        $trail->description = 'Ticket Created';
        $trail->save();

        $attachments = [];

        if ($request->hasFile('attachments')){
            foreach ($request->attachments as $file) {
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $path = FileStorageHelper::storeFile($ticket->getStoragePath(), $file);

                $attachment = new TicketAttachment();
                $attachment->title = $title;
                $attachment->ticket_id = $ticket->id;

                $attachment->file_url = $path;
                $attachment->file_type = $file->getMimeType();
                $attachment->file_size_in_kb = intval(ceil($file->getSize() / 1024));

                $attachment->save();
                array_push($attachments, $attachment);
            }
        }
        $ticket = $ticket->fresh();
        $ticket->load('attachments');

        return $this->respondCreated($ticket->toArray());
    }

    /**
     * Display the specified resource.
     *
     * @param Ticket $ticket
     * @return mixed
     * @throws AuthorizationException
     */
    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket = $ticket->load('category', 'user', 'attachments');
        $ticket->load(['replies.user', 'replies.attachments']);

        return $this->respond($ticket);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        return $this->respondNotFound();
    }

    /**
     * Update priority, status and assign users
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return mixed
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $input = $request->input();

        $ticket = Ticket::find($ticket->id);

        if ($request->has('priority')) {
            $ticket->priority = $input['priority'];
        }
        if ($request->has('status')) {
            $ticket->status = $input['status'];
        }
        $ticket->save();

        $ticketUser = new TicketUser();

        if ($request->has('assign_user')) {
            $ticketUser->ticket_id = $ticket->id;
            $ticketUser->user_id = $input['assign_user']['id'];
            $ticketUser->save();
        }

        if ($request->has('remove_assign')) {
            $ticketUser = $ticketUser->find($input['remove_assign']['id']);
            $ticketUser->delete();
        }

        $ticket = $ticket->fresh();
        $ticket->load('user');

        return $this->respond($ticket);
    }

    /**
     * Reply the ticket
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return \Illuminate\Http\RedirectResponse|mixed
     * @throws AuthorizationException
     */
    public function reply(Request $request, Ticket $ticket)
    {
        $this->authorize('reply', $ticket);

        $message = $request->input('message');

        if (empty($message)) {
            flash()->error('You need to enter a message.');
            return back();
        }

        $reply = new TicketReply;
        $reply->ticket_id = $ticket->id;
        $reply->user_id = Auth::user()->id;
        $reply->message = $message;
        if (Auth::user()->canAccessAdmin()) {
            $reply->status  = Ticket::STATUS_ANSWERED;
        }
        else {
            $reply->status  = Ticket::STATUS_CUSTOMER_REPLIED;
        }
        $reply->save();

        $attachments = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->attachments as $file) {
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $path = FileStorageHelper::storeFile($ticket->getStoragePath(), $file);

                $attachment = new TicketAttachment();
                $attachment->title = $title;
                $attachment->ticket_id = $ticket->id;
                $attachment->ticket_reply_id = $reply->id;

                $attachment->file_url = $path;
                $attachment->file_type = $file->getMimeType();
                $attachment->file_size_in_kb = intval(ceil($file->getSize() / 1024));

                $attachment->save();

                array_push($attachments, $attachment);
            }
        }

        $trail = new TicketTrails;
        $trail->user_id = Auth::user()->id;
        $trail->ticket_id = $ticket->id;
        $trail->action = TicketTrails::TICKET_REPLY;
        $trail->description = 'Ticket Replied';
        $trail->save();

        $reply = $reply->fresh();
        $reply->load('attachments');

        return $this->respondCreated($reply->toArray());
    }

    /**
     * Display ticket activity based on ticket id
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return mixed
     * @throws AuthorizationException
     */
    public function trail(Request $request, Ticket $ticket)
    {
        $this->authorize('trail', $ticket);

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);
        $ticket->load(['trails.user'])->paginate($limit);

        return $this->respond($ticket);
    }


    /**
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Ticket $ticket)
    {
        return $this->respondNotFound();
    }
}
