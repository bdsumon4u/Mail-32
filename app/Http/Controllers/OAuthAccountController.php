<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOAuthAccountRequest;
use App\Http\Requests\UpdateOAuthAccountRequest;
use App\Models\OAuthAccount;

class OAuthAccountController extends Controller
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
     * @param  \App\Http\Requests\StoreOAuthAccountRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOAuthAccountRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OAuthAccount  $oAuthAccount
     * @return \Illuminate\Http\Response
     */
    public function show(OAuthAccount $oAuthAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\OAuthAccount  $oAuthAccount
     * @return \Illuminate\Http\Response
     */
    public function edit(OAuthAccount $oAuthAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateOAuthAccountRequest  $request
     * @param  \App\Models\OAuthAccount  $oAuthAccount
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOAuthAccountRequest $request, OAuthAccount $oAuthAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OAuthAccount  $oAuthAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(OAuthAccount $oAuthAccount)
    {
        //
    }
}
