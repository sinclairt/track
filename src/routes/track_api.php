<?php

Route::get('/track', [
    'uses' => \Sinclair\Track\TrackController::class . '@index'
]);

Route::post('/track/filter', [
    'uses' => \Sinclair\Track\TrackController::class . '@filter'
]);

Route::get('/track/{track}', [
    'uses' => \Sinclair\Track\TrackController::class . '@show'
]);

Route::post('/track/object/', [
    'uses' => \Sinclair\Track\TrackController::class . '@byObject'
]);