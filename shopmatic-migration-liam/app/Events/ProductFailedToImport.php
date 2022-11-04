<?php

namespace App\Events;

use App\Models\ProductImportTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductFailedToImport
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProductImportTask $task, $message)
    {
        $this->task = $task;
        $this->message = $message;
    }
}
