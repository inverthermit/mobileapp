<?php

namespace App\Events;

use App\Models\Event;

use App\Events\BaseEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EventCreated extends BaseEvent
{
    use SerializesModels;

    public $event;

    /**
     * @param Event $event      [description]
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
