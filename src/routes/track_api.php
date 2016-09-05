<?php

Route::get('/api/v1/track', [
    'uses' => 'TrackController@index'
]);

Route::post('/api/v1/track/filter', [
    'uses' => 'TrackController@filter'
]);

Route::get('/api/v1/track/{track}', [
    'uses' => 'TrackController@show'
]);

Route::post('/api/v1/track/object/', [
    'uses' => 'TrackController@byObject'
]);