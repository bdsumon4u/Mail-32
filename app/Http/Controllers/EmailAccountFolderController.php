<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailAccountFolderRequest;
use App\Http\Requests\UpdateEmailAccountFolderRequest;
use App\Models\EmailAccountFolder;

class EmailAccountFolderController extends Controller
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
     * @param  \App\Http\Requests\StoreEmailAccountFolderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEmailAccountFolderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EmailAccountFolder  $emailAccountFolder
     * @return \Illuminate\Http\Response
     */
    public function show(EmailAccountFolder $emailAccountFolder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EmailAccountFolder  $emailAccountFolder
     * @return \Illuminate\Http\Response
     */
    public function edit(EmailAccountFolder $emailAccountFolder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateEmailAccountFolderRequest  $request
     * @param  \App\Models\EmailAccountFolder  $emailAccountFolder
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEmailAccountFolderRequest $request, EmailAccountFolder $emailAccountFolder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmailAccountFolder  $emailAccountFolder
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmailAccountFolder $emailAccountFolder)
    {
        //
    }
}
