<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $employeeId;
    public $name;
    public $latitude;
    public $longitude;

    public function __construct($employeeId, $name, $latitude, $longitude)
    {
        $this->employeeId = $employeeId;
        $this->name = $name;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('live-location'),
        ];
    }

    public function broadcastAs()
    {
        return 'location.updated';
    }
}
