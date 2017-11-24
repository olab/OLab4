<?php

namespace App\Listeners;

use App\Events\ExampleEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ExampleListener
{
    /**
     * ExampleListener constructor. Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     * @param ExampleEvent $event
     */
    public function handle(ExampleEvent $event)
    {
        //
    }
}
