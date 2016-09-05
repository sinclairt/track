<?php

namespace Sinclair\Track;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use League\Fractal\TransformerAbstract;
use Sinclair\ApiFoundation\Traits\ApiFoundation;
use Sinclair\ApiFoundation\Transformers\DefaultTransformer;

/**
 * Class TrackController
 * @package Sinclair\Track
 */
class TrackController extends Controller
{
    use ApiFoundation;

    /**
     * ApiFoundation constructor.
     *
     * @param TrackRepository $repository
     * @param TransformerAbstract|DefaultTransformer $transformer
     * @param null $resourceName
     */
    public function __construct( TrackRepository $repository, DefaultTransformer $transformer, $resourceName = null )
    {
        $this->repository = $repository;

        $this->transformer = $transformer;

        $this->resourceName = $resourceName;
    }

    /**
     * @param ByObject $request
     *
     * @return array
     */
    public function byObject( ByObject $request )
    {
        $class = new  $request->get('object_class');

        $object = $class->find($request->get('object_id'));

        if ( in_array(SoftDeletes::class, class_uses($class)) && is_null($object) )
            $object = $class->withTrashed()
                            ->find($request->get('object_id'));

        return $this->collection($this->repository->byObject($object));
    }
}