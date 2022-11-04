<?php

namespace App\Listeners;

use App\Events\ProductFailedToImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateProductImportTaskMessage
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ProductFailedToImport $event)
    {
        $event->task->messages = $this->setTaskMessages($event->task, $event->task->messages, $event->message);
        $event->task->save();
    }

    /**
     * Set task messages based on different condition
     *
     * @param string|array $messages
     * @param string $message
     * @return array
     */
    public function setTaskMessages($task, $messages, $message)
    {
        if (empty($messages)) {
            $messages = [$message];
        } else {
            $messages = array_merge((array)$task->messages, [$message]);
        }
        return $messages;
    }
}
