<?php

namespace Sinclair\Track;

use Auth, Log;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TrackTrait
 * @package Sinclair\Track
 *
 * @property array $original
 * @property array $attributes
 * @method static created( $callback, $priority = 0 )
 * @method static updating( $callback, $priority = 0 )
 * @method static updated( $callback, $priority = 0 )
 * @method static deleted( $callback, $priority = 0 )
 * @method static restored( $callback, $priority = 0 )
 * @method morphMany( $related, $name, $type = null, $id = null, $localKey = null )
 */
trait TrackTrait
{
    /**
     * @var array
     */
    public $syncResults = [];

    /**
     * @var array
     */
    protected $previousData = [];

    /**
     * @var array
     */
    protected $newData = [];

    /**
     * @var array
     */
    protected $timeStamps = [
        'created_at',
        'deleted_at',
        'updated_at'
    ];

    /**
     *
     */
    public static function bootTrackTrait()
    {
        static::created(function ( $model )
        {
            return $model->log('Created', $model);
        });

        static::updating(function ( $model )
        {
            $model->preSave();
        });

        static::updated(function ( $model )
        {
            $model->postSave();
        });

        static::deleted(function ( $model )
        {
            return $model->log('Deleted', $model);
        });

        if ( self::usesSoftDeletes() )
            static::restored(function ( $model )
            {
                return $model->log('Restored', $model);
            });
    }

    /**
     * @return bool
     */
    private static function usesSoftDeletes()
    {
        return in_array(SoftDeletes::class, class_uses(static::class));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function trackedChanges()
    {
        return $this->morphMany(Track::class, 'tracked');
    }

    /**
     * @param $event
     * @param $model
     * @param array $data
     */
    public function log( $event, $model, $data = [] )
    {
        $attributes = array_replace($data, $this->createData($event, $model));

        $attributes = array_filter($attributes, [ new Track, 'isFillable' ], ARRAY_FILTER_USE_KEY);

        Track::create($attributes);
    }

    /**
     *
     */
    public function preSave()
    {
        $this->previousData = array_filter($this->original, [ $this, 'isComparable' ], ARRAY_FILTER_USE_BOTH);

        $this->newData = array_intersect_key($this->attributes, $this->previousData);
    }

    /**
     * @param $value
     * @param $key
     *
     * @return bool
     */
    private function isComparable( $value, $key )
    {
        return !is_object($value) || !in_array($key, $this->timeStamps);
    }

    /**
     *
     */
    public function postSave()
    {
        $differences = array_diff_assoc($this->newData, $this->previousData);

        array_walk($differences, function ( &$item, $key )
        {
            $item = [ 'new' => $item, 'old' => array_get($this->previousData, $key) ];
        });

        if ( !$this->isRestoring($differences) )
            $this->logChanges($differences);
    }

    /**
     * @param $event
     * @param $model
     *
     * @return array
     */
    public function createData( $event, $model )
    {
        return [
            'user_id'      => $this->getUserId(),
            'tracked_type' => get_class($model),
            'tracked_id'   => $model->id,
            'event'        => $event
        ];
    }

    /**
     * @param $changed
     * @param $model
     * @param $method
     */
    public function logChanges( $changed, $model = null, $method = 'Updated' )
    {
        $model = is_null($model) ? $this : $model;

        array_walk($changed, [ $this, 'formatChanges' ], compact('model', 'method'));
    }

    private function formatChanges( $value, $key, $args )
    {
        extract($args);
        $data = [
            'field'     => $key,
            'new_value' => $value[ 'new' ],
            'old_value' => $value[ 'old' ]
        ];

        $data = array_replace($data, $this->createData($method, $model));

        $this->log('Updated', $model, $data);
    }

    /**
     * There is no event fired for syncing pivots so
     * this method must be called following a sync and
     * the result of a sync passed in
     *
     * @param $changes
     * @param TrackTrait $model
     * @param $class
     */
    public function trackPivotChanges( $changes, $model, $class )
    {
        $data = $this->trackAttached($changes[ 'attached' ], $model, $class);

        $this->trackDetached($changes[ 'detached' ], $model, $class, $data);
    }

    /**
     * @param array $changes
     * @param TrackTrait $model
     * @param string $class
     * @param array $data
     *
     * @return array
     */
    private function trackAttached( $changes, $model, $class, $data = [] )
    {
        foreach ( $changes as $item )
            $data[ $class ] = [
                'new' => $item,
                'old' => null
            ];

        $model->logChanges($data, $model, 'Attached');

        return $data;
    }

    /**
     * @param array $changes
     * @param TrackTrait $model
     * @param string $class
     * @param array $data
     *
     * @return array
     */
    private function trackDetached( $changes, $model, $class, $data )
    {
        foreach ( $changes as $item )
            $data[ $class ] = [
                'new' => null,
                'old' => $item
            ];

        $model->logChanges($data, $model, 'Detached');

        return $data;
    }

    /**
     * @return string
     */
    private function getUserId()
    {
        return is_null(Auth::user()) ? '' : Auth::user()
                                                ->getAuthIdentifier();
    }

    /**
     * @param $differences
     *
     * @return bool
     */
    private function isRestoring( $differences )
    {
        return sizeof($differences) == 1 && head(array_keys($differences)) == 'deleted_at';
    }

}