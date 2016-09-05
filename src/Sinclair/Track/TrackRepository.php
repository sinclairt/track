<?php

namespace Sinclair\Track;

use Sinclair\Repository\Repositories\Repository;

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

    public function byObject( $object )
    {
        $this->setModel($object->trackedChanges())
             ->filterPaginated(request(), request('rows', 15), request('orderBy', null), request('direction', 'asc'), request('columns', [ '*' ]), request('paginationName', 'page'), request('search', true));
    }
}