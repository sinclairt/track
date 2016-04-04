<?php

namespace Sinclair\Track;

use Laracasts\Presenter\PresentableTrait;
use App;

class Track extends \Eloquent implements TrackInterface
{
    use PresentableTrait;

    protected $presenter = 'Sinclair\Track\TrackPresenter';

    protected $guarded = [ 'id' ];

    protected $table = "changes";

    public function __construct( array $attributes = [ ] )
    {
        $this->presenter = config('track.presenter');

        parent::__construct($attributes);
    }

    public function tracked()
    {
        return $this->morphTo();
    }

    public static function createGroupedChanges( $changes, $where = [ ] )
    {
        $groupedChanges = [ ];

        foreach ( $changes as $change )
        {
            $key = class_basename($change->tracked_type);
            $class = App::make($change->tracked_type);
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

}