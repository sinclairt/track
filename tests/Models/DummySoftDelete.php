<?php

class DummySoftDelete extends \Illuminate\Database\Eloquent\Model
{
    use \Sinclair\Track\TrackTrait, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['name', 'number', 'plan'];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}