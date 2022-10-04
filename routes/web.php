<?php

use App\Http\Controllers\EmailAccountConnectionTestController;
use App\Http\Controllers\EmailAccountController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\TempMailController;
use App\Mail\BestMail;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('splade')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
    });

    require __DIR__.'/auth.php';

    Route::get('/temp-mail/new', [TempMailController::class, 'newMail'])->name('temp-mail.new');
    Route::get('/temp-mail/change', [TempMailController::class, 'changeMail'])->name('temp-mail.change');
    Route::get('/temp-mail/switch', [TempMailController::class, 'switchMail'])->name('temp-mail.switch');

    Route::get('/temp-mail/{folder?}/{message?}', TempMailController::class)->name('temp-mail');

    Route::post('test-connection', EmailAccountConnectionTestController::class)->name('connection.test');

    Route::get('/mail/accounts', MailController::class)->name('mail');
    Route::get('/mail/accounts/create', [MailController::class, 'create'])->name('mail.create');
    Route::post('/mail/accounts/create', [MailController::class, 'store'])->name('mail.store');
    Route::get('/mail/accounts/{emailAccount}/edit', [MailController::class, 'edit'])->name('mail.edit');
    Route::get('/mail/accounts/{type}/{provider}/connect', [MailController::class, 'connect'])->name('mail.connect');
    Route::resource('email-accounts', EmailAccountController::class);

    Route::get('/{providerName}/connect', [OAuthController::class, 'connect'])->where('providerName', 'microsoft|google');
    Route::get('/{providerName}/callback', [OAuthController::class, 'callback'])->where('providerName', 'microsoft|google');

    Route::get('/test', function () {
        Mail::send(new TestMail);
    });
    Route::get('/best', function () {
        Mail::send(new BestMail);
    });
});
