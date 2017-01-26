<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait WeavesResources
{

    /**
     * Show the form for creating a new Loom resource.
     *
     * @return Response
     */
    public function create()
    {
        return response('create');
    }

    /**
     * Remove the specified Loom resource from storage.
     *
     * @param  string  $id
     * @return Response
     */
    public function destroy($id)
    {
        return response('destroy');
    }

    /**
     * Show the form for editing the Loom specified resource.
     *
     * @param  string  $id
     * @return Response
     */
    public function edit($id)
    {
        return response('edit');
    }

    /**
     * Display a listing of the Loom resource.
     *
     * @return Response
     */
    public function index()
    {
        /** @var Model $resource */
        $resource = $this->getResourceClassName();
        return $resource::all();
    }


    /**
     * Display the specified Loom resource.
     *
     * @param  string  $id
     * @return Response
     */
    public function show($id)
    {
        $resource = $this->getResourceClassName();
        return $resource::find($id);
    }

    /**
     * Store a newly created Loom resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        return response('store');
    }

    /**
     * Update the specified Loom resource in storage.
     *
     * @param  Request  $request
     * @param  string  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        return response('update');
    }

    protected function getResourceClassName()
    {
        return \Loom::getResourceNamespace() . str_replace(
            [\Loom::getResourceControllerNamespace(), 'Controller'], '', static::class
        );
    }
}