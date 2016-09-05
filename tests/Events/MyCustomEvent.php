<?php

use Illuminate\Queue\SerializesModels;

class MyCustomEvent
{
    use SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        $this->message = 'something happened!';
    }
}