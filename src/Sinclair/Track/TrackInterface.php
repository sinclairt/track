<?php namespace Sterling\Track;

interface TrackInterface
{
	public function tracked();

	public static function createGroupedChanges($changes, $where = [ ]);
}