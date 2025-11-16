<?php

namespace Modules\ApiAuth\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\ApiAuth\Events\UserRegisteredEvent;
use Modules\ApiAuth\Notifications\UserRegisteredNotification;

class UserRegisteredListener
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(UserRegisteredEvent $event): void
    {
        $user = $event->user;

        $user->notify(new UserRegisteredNotification($event));
    }
}
