<?php

namespace Sinclair\Track;

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
        switch ($this->entity->event)
        {
            case "Updated":
                $message = $this->getUserDisplayName() . " updated the " . $this->entity->field . " field from " . $this->checkForEmpty($this->entity->old_value) . " to " . $this->checkForEmpty($this->entity->new_value);
                break;
            case "Attached":
                $message = $this->getUserDisplayName() . " attached " . $this->grammarise($this->getShortHandClassName($this->entity->field)) . " called " . $this->getClassDisplayName($this->entity->field, $this->entity->new_value);
                break;
            case "Detached":
                $message = $this->getUserDisplayName() . " detached " . $this->grammarise($this->getShortHandClassName($this->entity->field)) . " called " . $this->getClassDisplayName($this->entity->field, $this->entity->old_value);
                break;
            default:
                $message = $this->getUserDisplayName() . " " . strtolower($this->entity->event) . " " . $this->grammarise($this->getShortHandClassName()) . " with an ID of " . $this->entity->tracked_id;
        }

        return $message . " at " . $this->entity->updated_at;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    private function checkForEmpty( $value )
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
            $userModel = config('track.user.model');

            $user = new $userModel;

            $user = $user->findOrFail($this->entity->user_id);

            //check config for user display name
            if ( isset( $user->{config('track.user.displayName')} ) )
                return ucwords($user->{config('track.user.displayName')});

            return "An unknown user";
        }
        catch ( Exception $e )
        {
            return "An unknown user";
        }
    }

    /**
     * @param $class
     * @param $id
     *
     * @return string
     */
    private function getClassDisplayName( $class, $id )
    {
        try
        {
            $emptyObject = new $class;

            $object = $emptyObject->find($id);

            if ( $object == null )
                if ( method_exists($emptyObject, 'withTrashed') )
                    $object = $emptyObject->withTrashed()
                                          ->find($id);

            if ( isset( $object->name ) )
                return $object->name;

            if ( isset( $object->title ) )
                return $object->title;
        }
        catch ( Exception $e )
        {
            return "";
        }

    }

    /**
     * @param $class
     *
     * @return string
     */
    public function getShortHandClassName( $class = null )
    {
        $class = is_null($class) ? $this->entity->tracked_type : $class;

        $pos = strrpos($class, '\\');

        return substr($class, $pos + 1);
    }

    /**
     * @param $word
     *
     * @return string
     */
    public function grammarise( $word )
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