<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailAccountMessageFolderRequest;
use App\Http\Requests\UpdateEmailAccountMessageFolderRequest;
use App\Models\EmailAccountMessageFolder;

class EmailAccountMessageFolderController extends Controller
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
     * @param  \App\Http\Requests\StoreEmailAccountMessageFolderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEmailAccountMessageFolderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EmailAccountMessageFolder  $emailAccountMessageFolder
     * @return \Illuminate\Http\Response
     */
    public function show(EmailAccountMessageFolder $emailAccountMessageFolder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EmailAccountMessageFolder  $emailAccountMessageFolder
     * @return \Illuminate\Http\Response
     */
    public function edit(EmailAccountMessageFolder $emailAccountMessageFolder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateEmailAccountMessageFolderRequest  $request
     * @param  \App\Models\EmailAccountMessageFolder  $emailAccountMessageFolder
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEmailAccountMessageFolderRequest $request, EmailAccountMessageFolder $emailAccountMessageFolder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmailAccountMessageFolder  $emailAccountMessageFolder
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmailAccountMessageFolder $emailAccountMessageFolder)
    {
        //
    }
}
