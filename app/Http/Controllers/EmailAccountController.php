<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailAccountRequest;
use App\Http\Requests\UpdateEmailAccountRequest;
use App\Models\EmailAccount;

class EmailAccountController extends Controller
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
     * @param  \App\Http\Requests\StoreEmailAccountRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEmailAccountRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EmailAccount  $emailAccount
     * @return \Illuminate\Http\Response
     */
    public function show(EmailAccount $emailAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EmailAccount  $emailAccount
     * @return \Illuminate\Http\Response
     */
    public function edit(EmailAccount $emailAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateEmailAccountRequest  $request
     * @param  \App\Models\EmailAccount  $emailAccount
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEmailAccountRequest $request, EmailAccount $emailAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmailAccount  $emailAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmailAccount $emailAccount)
    {
        //
    }
}
