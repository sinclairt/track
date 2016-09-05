<?php

class Dummy extends \Illuminate\Database\Eloquent\Model
{
    use \Sinclair\Track\TrackTrait;

    protected $fillable = ['name', 'number', 'plan'];
}