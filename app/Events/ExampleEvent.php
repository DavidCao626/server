<?php

namespace App\Events;
use Illuminate\Queue\SerializesModels;

class ExampleEvent extends Event
{
	use SerializesModels;

	public $a;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($a)
    {
    	$this->a = $a;
    }
}
