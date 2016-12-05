<?php

namespace App\Events;

use App\Models\Event;

use App\Events\BaseEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EventUpdated extends BaseEvent
{
    use SerializesModels;

    public $event;
    public $inInvitees;
    public $outInvitees;
    public $curInvitees;
    public $updatedFields;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Event $event, $inInvitees, $outInvitees, $curInvitees, $updatedFields)
    {
        $this->event = $event;
        $this->inInvitees = $inInvitees;
        $this->outInvitees = $outInvitees;
        $this->curInvitees = $curInvitees;
        $this->updatedFields = $updatedFields;
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
