<?php

namespace App\Events;

use App\Models\Event;

use App\Events\BaseEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EventConfirmed extends BaseEvent
{
    use SerializesModels;

    public $event;
    /**
     * [these invitees is those whose selected time not equal to confirmed time]
     * @var [type]
     */
    public $resetInvitees;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Event $event, $resetInvitees = array())
    {
        $this->event = $event;
        $this->resetInvitees = $resetInvitees;
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
