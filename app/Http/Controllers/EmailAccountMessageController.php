<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailAccountMessageRequest;
use App\Http\Requests\UpdateEmailAccountMessageRequest;
use App\Models\EmailAccountMessage;

class EmailAccountMessageController extends Controller
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
     * @param  \App\Http\Requests\StoreEmailAccountMessageRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEmailAccountMessageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EmailAccountMessage  $emailAccountMessage
     * @return \Illuminate\Http\Response
     */
    public function show(EmailAccountMessage $emailAccountMessage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EmailAccountMessage  $emailAccountMessage
     * @return \Illuminate\Http\Response
     */
    public function edit(EmailAccountMessage $emailAccountMessage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateEmailAccountMessageRequest  $request
     * @param  \App\Models\EmailAccountMessage  $emailAccountMessage
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEmailAccountMessageRequest $request, EmailAccountMessage $emailAccountMessage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmailAccountMessage  $emailAccountMessage
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmailAccountMessage $emailAccountMessage)
    {
        //
    }
}
