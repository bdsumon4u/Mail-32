<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailAccountMessageHeaderRequest;
use App\Http\Requests\UpdateEmailAccountMessageHeaderRequest;
use App\Models\EmailAccountMessageHeader;

class EmailAccountMessageHeaderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreEmailAccountMessageHeaderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEmailAccountMessageHeaderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EmailAccountMessageHeader  $emailAccountMessageHeader
     * @return \Illuminate\Http\Response
     */
    public function show(EmailAccountMessageHeader $emailAccountMessageHeader)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EmailAccountMessageHeader  $emailAccountMessageHeader
     * @return \Illuminate\Http\Response
     */
    public function edit(EmailAccountMessageHeader $emailAccountMessageHeader)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateEmailAccountMessageHeaderRequest  $request
     * @param  \App\Models\EmailAccountMessageHeader  $emailAccountMessageHeader
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEmailAccountMessageHeaderRequest $request, EmailAccountMessageHeader $emailAccountMessageHeader)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmailAccountMessageHeader  $emailAccountMessageHeader
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmailAccountMessageHeader $emailAccountMessageHeader)
    {
        //
    }
}
