<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailAccountMessageAddressRequest;
use App\Http\Requests\UpdateEmailAccountMessageAddressRequest;
use App\Models\EmailAccountMessageAddress;

class EmailAccountMessageAddressController extends Controller
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
     * @param  \App\Http\Requests\StoreEmailAccountMessageAddressRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEmailAccountMessageAddressRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EmailAccountMessageAddress  $emailAccountMessageAddress
     * @return \Illuminate\Http\Response
     */
    public function show(EmailAccountMessageAddress $emailAccountMessageAddress)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EmailAccountMessageAddress  $emailAccountMessageAddress
     * @return \Illuminate\Http\Response
     */
    public function edit(EmailAccountMessageAddress $emailAccountMessageAddress)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateEmailAccountMessageAddressRequest  $request
     * @param  \App\Models\EmailAccountMessageAddress  $emailAccountMessageAddress
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEmailAccountMessageAddressRequest $request, EmailAccountMessageAddress $emailAccountMessageAddress)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmailAccountMessageAddress  $emailAccountMessageAddress
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmailAccountMessageAddress $emailAccountMessageAddress)
    {
        //
    }
}
