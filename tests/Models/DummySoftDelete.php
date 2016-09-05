<?php

class DummySoftDelete extends \Illuminate\Database\Eloquent\Model
{
    use \Sinclair\Track\TrackTrait, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['name', 'number', 'plan'];
}