<?php

namespace Sinclair\Track;

/**
 * Class TrackEventListener
 * @package Sinclair\Track
 */
class TrackEventListener
{
    /**
     * @param $event
     */
    public function track( $event )
    {
        Track::create([
            'user_id'      => is_null(auth()->user()) ? '' : auth()
                ->user()
                ->getAuthIdentifier(),
            'tracked_type' => get_class($event),
            'tracked_id'   => 0,
            'event'        => get_class($event)
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher $events
     */
    public function subscribe( $events )
    {
        foreach ( config('track.events', []) as $event )
            $events->listen($event, self::class . '@track');
    }
}