<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Models\EmailAccountMessage;
use App\Models\TempMail;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TempMailController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if ($email = Cookie::get('temp-mail')) {
        } else {
            $tempMail = $this->generateTempMail();
            $minutes = $tempMail->expires_at->diffInMinutes();
            Cookie::queue('temp-mail', $email = $tempMail->address, $minutes);
        }

        return view('mail.temp-mail', [
            'email' => $email,
            'messages' => EmailAccountMessage::query()
                ->with(['from', 'to', 'cc', 'bcc', 'replyTo', 'sender'])
                ->whereHas('to', fn ($query) => $query->where('address', $email))
                // ->take(15)
                ->paginate(5),
        ]);
    }

    private function generateTempMail(): TempMail
    {
        $account = EmailAccount::query()->inRandomOrder()->firstOrFail();

        $username = Str::of($email = $account->email)
            ->before(Str::contains($email, '+') ? '+' : '@')
            ->toString();

        return retry(5, function () use ($username, $account) {
            $temp = $this->tempName($username);

            return $account->tempMails()->create([
                'address' => Str::replace($username, $temp, $account->email),
                'expires_at' => now()->addMinutes(32),
            ]);
        });
    }

    private function tempName($username)
    {
        $path = 'temp-name/'.$username.'.json';

        if (! $result = Storage::get($path)) {
            $placeholder = implode('*', str_split($username));
            $result = $this->tempHelper($placeholder);
            Storage::put($path, json_encode($result));

            return Arr::random($result);
        }

        return Arr::random(json_decode($result));
    }

    private function tempHelper(&$string, $i = 0)
    {
        static $result = [];

        if ($i == strlen($string)) {
            $result[] = preg_replace('/\.+/', '.', Str::remove('*', $string));

            return $result;
        }

        if ($string[$i] != '*') {
            return $this->tempHelper($string, $i + 1);
        }

        $this->tempHelper($string, $i + 1);
        $string[$i] = '.';
        $this->tempHelper($string, $i + 1);
        $string[$i] = '*';

        return $result;
    }
}
