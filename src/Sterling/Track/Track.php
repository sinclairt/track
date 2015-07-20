<?php

namespace Sterling\Track;

use Laracasts\Presenter\PresentableTrait;

class Track extends \Eloquent implements TrackInterface
{
	use PresentableTrait;

	protected $presenter = 'Sterling\Track\TrackPresenter';

	protected $guarded = [ 'id' ];

	protected $table = "changes";

	public function tracked()
	{
		return $this->morphTo();
	}

	public static function createGroupedChanges($changes, $where = [ ])
	{
		$groupedChanges = [ ];

		foreach ($changes as $change)
		{
			$key = class_basename($change->tracked_type);
			$class = App::make($change->tracked_type);
			$object = $class->find($change->tracked_id);
			$objectName = $object->name == null ? $object->title : $object->name;
			$key .= ": " . $objectName;
			$groupedChanges[ $key ] = Track::where('tracked_id', $object->id)
										   ->where('tracked_type', get_class($object))
										   ->where(function ($q) use ($where)
										   {
											   if (sizeof($where) == 3)
												   $q->where($where[ 0 ], $where[ 1 ], $where[ 2 ]);
										   })
										   ->get();
		}

		return $groupedChanges;
	}

}