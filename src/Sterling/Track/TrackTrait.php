<?php

namespace Sterling\Track;

use Auth, Log, Exception, App;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class TrackTrait
 * @package Sterling\Track
 */
trait TrackTrait
{

	/**
	 * @var
	 */
	public $syncResults;

	/**
	 * @var
	 */
	protected $previousData;

	/**
	 * @var
	 */
	protected $newData;

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
	public static function boot()
	{

		parent::boot();

		// Model Events: creating, created, updating, updated, saving, saved, deleting, deleted, restoring, restored

		static::created(function ($model)
		{
			return $model->log('Created', $model);
		});

		static::updating(function ($model)
		{
			$model->preSave();
		});

		static::updated(function ($model)
		{
			$model->postSave();
		});

		static::deleted(function ($model)
		{
			return $model->log('Deleted', $model);
		});

		// this method is provided by the soft delete trait -- if this throws and exception we can just ignore this method
		try
		{
			static::restored(function ($model)
			{
				return $model->log('Restored', $model);
			});

		}
		catch (Exception $e)
		{
			//do nothing
		}

	}

	/**
	 * @return mixed
	 */
	public function trackedChanges()
	{
		return $this->morphMany('Sterling\Track\Track', 'tracked');
	}

	/**
	 * @param $event
	 * @param $model
	 * @param null $data
	 */
	public function log($event, $model, $data = null)
	{
		$data = is_null($data) ? $this->createData($event, $model) : $data;

		Track::create($data);
	}

	/**
	 *
	 */
	public function preSave()
	{
		// get values
		$this->previousData = $this->original;
		$this->newData = $this->attributes;

		// drop anything we cant compare
		foreach ($this->newData as $key => $value)
		{
			$this->removeUnComparable($value, $key);

		}

		$this->dropTimeStamps();

	}

	/**
	 *
	 */
	public function postSave()
	{
		$changed = [ ];
		foreach ($this->newData as $key => $value)
		{
			$changed = $this->checkForChange($key, $value, $changed);
		}

		$this->logChanges($changed);
	}

	/**
	 * @param $event
	 * @param $model
	 *
	 * @return array
	 */
	public function createData($event, $model)
	{

		$user_id = is_null(Auth::user()) ? '' : Auth::user()
													->getAuthIdentifier();

		return [
			'user_id' => $user_id,
			'tracked_type' => get_class($model),
			'tracked_id' => $model->id,
			'event' => $event
		];
	}

	/**
	 * @param $key
	 * @param $value
	 * @param $changed
	 *
	 * @return mixed
	 */
	private function checkForChange($key, $value, $changed)
	{
		if (isset($this->previousData[ $key ]))
		{
			if ($this->previousData[ $key ] !== $value)
			{
				$changed[ $key ] = [
					'new' => $value,
					'old' => $this->previousData[ $key ]
				];

				return $changed;
			}
		}

		return $changed;
	}

	/**
	 * @param $changed
	 * @param $model
	 * @param $method
	 */
	public function logChanges($changed, $model = null, $method = 'Updated')
	{
		$model = is_null($model) ? $this : $model;

		foreach ($changed as $key => $value)
		{
			$data = $this->createData($method, $this);
			$data[ 'field' ] = $key;
			$data[ 'new_value' ] = $value[ 'new' ];
			$data[ 'old_value' ] = $value[ 'old' ];

			$this->log("Updated", $model, $data);
		}
	}

	/**
	 *
	 */
	private function dropTimeStamps()
	{
		// drop time stamps
		foreach ($this->timeStamps as $value)
		{
			unset($this->previousData[ $value ]);
			unset($this->newData[ $value ]);
		}
	}

	/**
	 * @param $value
	 * @param $key
	 */
	private function removeUnComparable($value, $key)
	{
		if (gettype($value) == 'object')
		{
			unset($this->previousData[ $key ]);
			unset($this->newData[ $key ]);
		}
	}

	/**
	 * There is no event fired for syncing pivots so
	 * this method must be called following a sync and
	 * the result of a sync passed in
	 *
	 * @param $changes
	 * @param $model
	 * @param $class
	 */
	public function trackPivotChanges($changes, $model, $class)
	{
		$data = [ ];
		foreach ($changes[ 'attached' ] as $item)
		{
			$data[ $class ] = [
				'new' => $item,
				'old' => null
			];
			$model->logChanges($data, $model, 'Attached');
		}

		foreach ($changes[ 'detached' ] as $item)
		{
			$data[ $class ] = [
				'new' => null,
				'old' => $item
			];
			$model->logChanges($data, $model, 'Detached');
		}
	}

}