<?php

namespace App\Http\Controllers;

use App\Enums\ConnectionType;
use App\Enums\EmailAccountType;
use App\Http\Requests\StoreEmailAccountRequest;
use App\Http\Resources\EmailAccountResource;
use App\Innoclapps\Facades\OAuthState;
use App\Innoclapps\MailClient\FolderCollection;
use App\Innoclapps\OAuth\OAuthManager;
use App\Models\EmailAccount;
use App\Models\EmailAccountFolder;
use Illuminate\Http\Request;

class MailController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $query = EmailAccount::query();
        if ($request->query('viaOAuth')) {
            dd($query->orderBy('id', 'desc')->first());

            return redirect()->action([static::class, 'edit'], $query->orderBy('id', 'desc')->first());
        }

        return view('mail.accounts');
    }

    public function create()
    {
        return view('mail.create', [
            'connection_types' => $this->connectionTypes(),
        ]);
    }

    public function store(StoreEmailAccountRequest $request)
    {
        if (! $request->isImapConnectionType()) {
            return redirect()->action([static::class, 'connect'], [
                'type' => 'personal',
                'provider' => match ($request->connection_type) {
                    ConnectionType::Gmail->value => 'google',
                    ConnectionType::Outlook->value => 'microsoft',
                    default => '',
                },
                'period' => strtotime($request->initial_sync_from),
                're_auth' => false,
            ]);
        }
        // IMAP
        $account = EmailAccount::query()->updateOrCreate([
            'email' => $request->email,
        ], $request->validated());

        if (! $request->exists('folders')) {
            // Test Connection

            $imapFolders = (new EmailAccountConnectionTestController)($request)->getData(true)['folders'];
            foreach ($imapFolders as $folder) {
                $this->persistForAccount($account, $folder);
            }

            return back()->with('imapFolders', (new EmailAccountConnectionTestController)($request)->getData(true));
        }

        return EmailAccountResource::make($account);
    }

    /**
     * Update folder for a given account
     *
     * @param  \App\Models\EmailAccount  $account
     * @param  array  $folder
     * @return \App\Models\EmailAccountFolder
     */
    public function persistForAccount(EmailAccount $account, array $folder)
    {
        $parent = EmailAccountFolder::updateOrCreate(
            $this->getUpdateOrCreateAttributes($account, $folder),
            array_merge($folder, [
                'email_account_id' => $account->id,
                'syncable' => $folder['syncable'] ?? false,
            ])
        );

        $this->handleChildFolders($parent, $folder, $account);

        return $parent;
    }

    /**
     * Handle the child folders creation process
     *
     * @param  \App\Models\EmailAccountFolder  $parentFolder
     * @param  array  $folder
     * @param  \App\Models\EmailAccount  $account
     * @return void
     */
    protected function handleChildFolders($parentFolder, $folder, $account)
    {
        // Avoid errors if the children key is not set
        if (! isset($folder['children'])) {
            return;
        }

        if ($folder['children'] instanceof FolderCollection) {
            /**
             * @see \App\Listeners\CreateEmailAccountViaOAuth
             */
            $folder['children'] = $folder['children']->toArray();
        }

        foreach ($folder['children'] as $child) {
            $parent = $this->persistForAccount($account, array_merge($child, [
                'parent_id' => $parentFolder->id,
            ]));

            $this->handleChildFolders($parent, $child, $account);
        }
    }

    /**
     * Get the attributes that should be used for update or create method
     *
     * @param  \App\Models\EmailAccount  $account
     * @param  array  $folder
     * @return array
     */
    protected function getUpdateOrCreateAttributes($account, $folder)
    {
        $attributes = ['email_account_id' => $account->id];

        // If the folder database ID is passed
        // use the ID as unique identifier for the folder
        if (isset($folder['id'])) {
            $attributes['id'] = $folder['id'];
        } else {
            // For imap account, we use the name as unique identifier
            // as the remote_id may not always be unique
            if ($account->connection_type === ConnectionType::Imap) {
                $attributes['name'] = $folder['name'];
            } else {
                // For API based accounts e.q. Gmail and Outlook
                // we use the remote_id as unique identifier
                $attributes['remote_id'] = $folder['remote_id'];
            }
        }

        return $attributes;
    }

    public function edit(EmailAccount $emailAccount)
    {
        dd($emailAccount);
    }

    /**
     * OAuth connect email account
     *
     * @param  string  $type shared|personal
     * @param  string  $providerName
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Innoclapps\OAuth\OAuthManager  $manager
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connect($type, $providerName, Request $request, OAuthManager $manager)
    {
        // abort_if(
        //     ! $request->user()->isSuperAdmin() && EmailAccountType::from($type) === EmailAccountType::SHARED,
        //     403,
        //     'Unauthorized action.'
        // );

        return back()->with('authLink', $manager->createProvider($providerName)
            ->getAuthorizationUrl(['state' => $this->createState($request, $type, $manager)]));
    }

    /**
     * Create state
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @param  \App\Innoclapps\OAuth\OAuthManager  $manager
     * @return string
     */
    protected function createState($request, $type, $manager)
    {
        return OAuthState::putWithParameters([
            'return_url' => '/mail/accounts?viaOAuth=true',
            'period' => $request->period,
            'email_account_type' => $type,
            're_auth' => $request->re_auth,
            'key' => $manager->generateRandomState(),
        ]);
    }

    private function connectionTypes()
    {
        return array_combine(array_column(ConnectionType::cases(), 'value'), array_column(ConnectionType::cases(), 'name'));
    }
}
