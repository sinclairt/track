<?php

return [
	'user'      => [
		'model'       => '\App\User',
		'displayName' => 'username',
	],
	'presenter' => \Sinclair\Track\TrackPresenter::class,
	'events'    => [
		// add your FQN events here
		MyCustomEvent::class,
	],
	'table'     => 'changes',
	'non-user-value'  => 0,
];