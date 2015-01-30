<?php namespace Sterling\Track;

use Laracasts\Presenter\Presenter;
use Config;
use Exception;

/**
 * Class TrackPresenter
 * @package Sterling\Track
 */
class TrackPresenter extends Presenter {

	/**
	 * @return string
	 */
	public function prettyChange()
	{
		switch ($this->event)
		{
			case "Updated":
				$message = $this->getUserDisplayName() . " updated the " . $this->field . " field from " . $this->old_value . " to " . $this->new_value;
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

	/**
	 * @return mixed
	 */
	private function getUserDisplayName()
	{
		try
		{
			// check config for user model
			$userModel = Config::get('track::user.model') == '' ? Config::get('auth.model') : Config::get('track::user.model');
			$user = new $userModel;
			$user = $user->findOrFail($this->user_id);
			//check config for user display name
			$userDisplayName = Config::get('track::user.displayName');

			return ucwords($user->$userDisplayName);
		} catch (Exception $e)
		{
			return "An unknown user";
		}
	}

	private function getClassDisplayName($class, $id)
	{
		$object = new $class;
		$object = $object->find($id);
		return $object->name;
	}

	/**
	 * @param $class
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
		$vowels = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'];
		$firstLetter = substr($word, 0, 1);

		return in_array($firstLetter, $vowels) ? 'an ' . $word : 'a ' . $word;
	}

} 