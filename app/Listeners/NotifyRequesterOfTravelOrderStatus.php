<?php

namespace App\Listeners;

use App\Events\TravelOrderStatusRecorded;
use App\Notifications\TravelOrderStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyRequesterOfTravelOrderStatus implements ShouldQueue
{
    public function handle(TravelOrderStatusRecorded $event): void
    {
        $event->travelOrder->user->notify(
            new TravelOrderStatusChangedNotification($event->travelOrder)
        );
    }
}
