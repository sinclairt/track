<?php namespace Sinclair\Track;

use Laracasts\Presenter\Presenter;
use Config;
use Exception;

/**
 * Class TrackPresenter
 * @package Sinclair\Track
 */
class TrackPresenter extends Presenter
{

	/**
	 * @return string
	 */
	public function prettyChange()
	{
		switch ($this->event)
		{
			case "Updated":
				$message = $this->getUserDisplayName() . " updated the " . $this->field . " field from " . $this->checkForEmpty($this->old_value) . " to " . $this->checkForEmpty($this->new_value);
				break;
			case "Attached":
				$message = $this->getUserDisplayName() . " attached " . $this->grammarise($this->getShortHandClassName($this->field)) . " called " . $this->getClassDisplayName($this->field, $this->new_value);
				break;
			case "Detached":
				$message = $this->getUserDisplayName() . " detached " . $this->grammarise($this->getShortHandClassName($this->field)) . " called " . $this->getClassDisplayName($this->field, $this->old_value);
				break;
			default:
				$message = $this->getUserDisplayName() . " " . strtolower($this->event) . " " . $this->grammarise($this->getShortHandClassName()) . " with an ID of " . $this->tracked_id;
		}

		return $message . " at " . $this->updated_at;
	}

	private function checkForEmpty($value)
	{
		return $value == null or $value == '' ? '{empty}' : $value;
	}

	/**
	 * @return mixed
	 */
	private function getUserDisplayName()
	{
		try
		{
			// check config for user model
			$user = new Config::get('track.user.model');
			$user = $user->findOrFail($this->user_id);
			//check config for user display name
			if (isset($user->username))
			{
				return ucwords($user->username);
			}
			elseif (isset($user->name))
			{
				return ucwords($user->name);
			}

			return "An unknown user";
		}
		catch (Exception $e)
		{
			return "An unknown user";
		}
	}

	private function getClassDisplayName($class, $id)
	{
		try
		{
			$emptyObject = new $class;
			$object = $emptyObject->find($id);

			if($object == null)
				if(method_exists($emptyObject, 'withTrashed'))
					$object = $emptyObject->withTrashed()->find($id);

			if (isset($object->name))
				return $object->name;

			if (isset($object->title))
				return $object->title;
		}catch(Exception $e)
		{
			return "";
		}

	}

	/**
	 * @param $class
	 *
	 * @return string
	 */
	public function getShortHandClassName($class = null)
	{
		$class = is_null($class) ? $this->tracked_type : $class;

		$pos = strrpos($class, '\\');

		return substr($class, $pos + 1);
	}

	public function grammarise($word)
	{
		$vowels = [
			'a',
			'e',
			'i',
			'o',
			'u',
			'A',
			'E',
			'I',
			'O',
			'U'
		];
		$firstLetter = substr($word, 0, 1);

		return in_array($firstLetter, $vowels) ? 'an ' . $word : 'a ' . $word;
	}

} 