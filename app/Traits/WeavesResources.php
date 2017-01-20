<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait WeavesResources
{

    /**
     * Show the form for creating a new Webstuhl resource.
     *
     * @return Response
     */
    public function create()
    {
        return response('create');
    }

    /**
     * Remove the specified Webstuhl resource from storage.
     *
     * @param  string  $id
     * @return Response
     */
    public function destroy($id)
    {
        return response('destroy');
    }

    /**
     * Show the form for editing the Webstuhl specified resource.
     *
     * @param  string  $id
     * @return Response
     */
    public function edit($id)
    {
        return response('edit');
    }

    /**
     * Display a listing of the Webstuhl resource.
     *
     * @return Response
     */
    public function index()
    {
        return response('index');
    }


    /**
     * Display the specified Webstuhl resource.
     *
     * @param  string  $id
     * @return Response
     */
    public function show($id)
    {
        return response('show');
    }

    /**
     * Store a newly created Webstuhl resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        return response('store');
    }

    /**
     * Update the specified Webstuhl resource in storage.
     *
     * @param  Request  $request
     * @param  string  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        return response('update');
    }

}