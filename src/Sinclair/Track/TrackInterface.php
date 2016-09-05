<?php

namespace Sinclair\Track;

/**
 * Interface TrackInterface
 * @package Sinclair\Track
 */
interface TrackInterface
{
    /**
     * @return mixed
     */
    public function tracked();

    /**
     * @param array $changes
     * @param array $where
     *
     * @return mixed
     */
    public static function createGroupedChanges( $changes, $where = [] );
}