<?php

namespace Sinclair\Track;

use Illuminate\Pagination\LengthAwarePaginator;
use Sinclair\Repository\Repositories\Repository;

/**
 * Class TrackRepository
 * @package Sinclair\Track
 */
class TrackRepository extends Repository implements \Sinclair\Repository\Contracts\Repository
{
    /**
     * TrackRepository constructor.
     *
     * @param Track $model
     */
    public function __construct( Track $model )
    {
        $this->model = $model;
    }

    /**
     * @param $object
     *
     * @return LengthAwarePaginator
     */
    public function byObject( $object )
    {
        $this->query = $object->trackedChanges();

        foreach ( request()->except('_token') as $key => $value )
            $this->{'filter' . studly_case($key)}(request(), request('search', true));

        return $this->sort($this->query, request('orderBy'), request('direction'))
                    ->paginate(request('rows'), request('columns', [ '*' ]), request('pagination_name', 'page'));
    }
}