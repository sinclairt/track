<?php namespace Sterling\Track;

interface TrackInterface
{
	public function tracked();

	public function createGroupedChanges($changes, $where = [ ]);
}