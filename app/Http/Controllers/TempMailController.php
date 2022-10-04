<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Models\EmailAccountFolder;
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
    public function __invoke(Request $request, ?EmailAccountFolder $folder, ?EmailAccountMessage $message)
    {
        $list = $this->getTempList();

        if ($email = Cookie::get('temp-mail-active')) {
            $tempMail = TempMail::with('emailAccount.folders')->where('address', $email)->firstOrFail();
        } else {
            $tempMail = $this->giveMeNewTempMail($list);
        }

        $messages = ! $folder->exists || $message->exists ? null
            : $folder->messages()
            // ->whereHas('from'... for sent messages.
            ->whereHas('to', fn ($query) => $query->where('address', $email))
            ->orWhereHas('cc', fn ($query) => $query->where('address', $email))
            ->orWhereHas('bcc', fn ($query) => $query->where('address', $email))
            ->with(['from'])->simplePaginate(5);

        return view('mail.temp-mail', [
            'list' => $list,
            'tempMail' => $tempMail,
            'messages' => $messages,
            'message' => $message,
            'folder' => $folder,
        ]);
    }

    private function getTempList()
    {
        return json_decode(Cookie::get('temp-mail-list', '[]'), true);
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
            ])->setRelation('emailAccount', $account->load('folders'));
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

    private function giveMeNewTempMail($list = null): TempMail
    {
        $tempMail = $this->generateTempMail();
        $list[$address = $tempMail->address] = $address;
        $minutes = $tempMail->expires_at->diffInMinutes();
        Cookie::queue('temp-mail-list', json_encode($list));
        Cookie::queue('temp-mail-active', $address, $minutes);

        return $tempMail;
    }

    public function newMail(Request $request)
    {
        $this->giveMeNewTempMail($this->getTempList());

        return back();
    }

    public function changeMail(Request $request)
    {
    }

    public function switchMail(Request $request)
    {
        Cookie::queue('temp-mail-active', $request->email);

        return back();
    }
}
