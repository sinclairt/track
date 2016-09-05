<?php

namespace Sinclair\Track;

use Illuminate\Database\Eloquent\Model;
use Laracasts\Presenter\PresentableTrait;

/**
 * Class Track
 * @package Sinclair\Track
 */
class Track extends Model implements TrackInterface
{
    use PresentableTrait;

    /**
     * @var mixed|string
     */
    protected $presenter = 'Sinclair\Track\TrackPresenter';

    /**
     * @var array
     */
    protected $fillable = [ 'tracked_type', 'tracked_id', 'user_id', 'event', 'field', 'old_value', 'new_value' ];

    /**
     * @var string
     */
    protected $table = "changes";

    /**
     * Track constructor.
     *
     * @param array $attributes
     */
    public function __construct( array $attributes = [] )
    {
        $this->presenter = config('track.presenter');

        parent::__construct($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function tracked()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(config('track.user.model'));
    }

    /**
     * @param array $changes
     * @param array $where
     *
     * @return array
     */
    public static function createGroupedChanges( $changes, $where = [] )
    {
        $groupedChanges = [];

        foreach ( $changes as $change )
        {
            $key = class_basename($change->tracked_type);

            $class = app($change->tracked_type);

            $object = method_exists($class, 'withTrashed') ? $class->withTrashed()
                                                                   ->find($change->tracked_id) : $class->find($change->tracked_id);

            $objectName = $object->name == null ? $object->title : $object->name;

            $key .= ": " . $objectName;

            $groupedChanges[ $key ] = Track::where('tracked_id', $object->id)
                                           ->where('tracked_type', get_class($object))
                                           ->where(function ( $q ) use ( $where )
                                           {
                                               if ( sizeof($where) == 3 )
                                                   $q->where($where[ 0 ], $where[ 1 ], $where[ 2 ]);
                                           })
                                           ->get();
        }

        return $groupedChanges;
    }

    /**
     * @param $query
     * @param $value
     * @param bool $trashed
     *
     * @return mixed
     */
    public function scopeFilterEvent( $query, $value, $trashed = false )
    {
        return $query->where('event', $value);
    }

    /**
     * @param $query
     * @param $value
     * @param bool $trashed
     *
     * @return mixed
     */
    public function scopeFilterTrackedType( $query, $value, $trashed = false )
    {
        return $query->where('tracked_type', $value);
    }

    /**
     * @param $query
     * @param $value
     * @param bool $trashed
     *
     * @return mixed
     */
    public function scopeFilterTrackedId( $query, $value, $trashed = false )
    {
        return $query->where('tracked_id', $value);
    }

    /**
     * @param $query
     * @param $value
     * @param bool $trashed
     *
     * @return mixed
     */
    public function scopeFilterUserId( $query, $value, $trashed = false )
    {
        return $query->where('user_id', $value);
    }

    /**
     * @param $query
     * @param $value
     * @param bool $trashed
     *
     * @return mixed
     */
    public function scopeFilterField( $query, $value, $trashed = false )
    {
        return $query->where('field', $value);
    }

    /**
     * @param $query
     * @param $value
     * @param bool $trashed
     *
     * @return mixed
     */
    public function scopeFilterOldValue( $query, $value, $trashed = false )
    {
        return $query->where('old_value', $value);
    }

    /**
     * @param $query
     * @param $value
     * @param bool $trashed
     *
     * @return mixed
     */
    public function scopeFilterNewValue( $query, $value, $trashed = false )
    {
        return $query->where('new_value', $value);
    }
}