<?php namespace Sterling\Track; 

use Laracasts\Presenter\PresentableTrait;

class Track extends \Eloquent implements TrackInterface {

	use PresentableTrait;

	protected $presenter = 'Sterling\Track\TrackPresenter';

    protected $guarded = ['id'];

	protected $table = "changes";

	public function tracked()
	{
		return $this->morphTo();
	}

}