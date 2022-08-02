<?php

namespace App\Events\Infinity;

use App\Events\Event;
use App\Services\InfinityService;
use App\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Cache;

class ClosingModalsEvent extends Event implements ShouldBroadcast
{
    private $userExtension;

    /**
     * IncomingCallEvent constructor.
     * @param $number
     * @param $callId
     * @param $userExtension
     */
    public function __construct($userExtension)
    {
        $this->userExtension = $userExtension;
    }

    /**
     * @return array
     */
    public function broadcastWith()
    {
        return [];
    }

    /**
     * @return string
     */
    public function broadcastAs()
    {
        return 'closing_modals';
    }

    /**
     * @return string[]
     */
    public function broadcastOn()
    {
        return [
            'infinity_calls_' . $this->userExtension,
        ];
    }
}
