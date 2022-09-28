<?php

namespace App\Console\Commands;

use App\Events\EmailAccountsSyncFinished;
use App\MailClient\EmailAccountSynchronizationManager;
use App\MailClient\Exceptions\SynchronizationInProgressException;
use App\Models\EmailAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EmailAccountsSyncCommand extends Command
{
    /**
     * The lock key for the settings
     *
     * @var string
     */
    const LOCK_KEY = 'email-accounts-sync-in-progress';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:sync
                        {--account= : Email account ID}
                        {--broadcast : Whether to broadcast events}
                        {--manual : Whether the sync is invoked manually via the UI}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronizes email accounts.';

    /**
     * Execute the console command.
     *
     * @param  \App\Contracts\Repositories\EmailAccountRepository  $repository
     * @return void
     *
     * @throws App\MailClient\Exceptions\SynchronizationInProgressException
     */
    public function handle()
    {
        $this->checkIfAlreadyRunning();

        $this->info('Gathering email accounts to sync.');

        $accounts = $this->getAccounts();

        if ($accounts->isEmpty()) {
            $this->error('No accounts found for synchronization.');
        }

        $this->sync($accounts);
    }

    /**
     * Sync the email accounts
     *
     * @param  \Illuminate\Support\Collection<\App\Models\EmailAccount>  $accounts
     * @return void
     */
    protected function sync($accounts)
    {
        $synced = false;
        // When the "inital sync from" option "now" is selected and the sync runs for first time
        // and if nothing is synchronized the UI message that initial sync is not performed won't be removed
        // In this case, will make sure to broadcast so the accounts are refetched
        $hasInitialSync = false;

        foreach ($accounts as $account) {
            if (! $account->isInitialSyncPerformed()) {
                $hasInitialSync = true;
            }

            $this->info(sprintf('Starting synchronization for account %s.', $account->email));

            if (EmailAccountSynchronizationManager::getSynchronizer($account)->setCommand($this)->perform()) {
                $synced = true;
            }

            $account->update(['last_sync_at' => Carbon::now()]);
        }

        if ($this->option('broadcast')) {
            event(new EmailAccountsSyncFinished($synced || $hasInitialSync));
        }
    }

    /**
     * Get the accounts that should be synced
     *
     * @param  \App\Contracts\Repositories\EmailAccountRepository  $repository
     * @return \Illuminate\Support\Collection
     */
    protected function getAccounts()
    {
        return EmailAccount::with(['oAuthAccount', 'folders'])->syncable()
            ->when($this->option('account'), function ($query) {
                $query->where('id', (int) $this->option('account'));
            })
            ->get();

        // HOTASH #
        // if ($this->option('account')) {
        //     $accounts = $accounts->filter(function ($account) {
        //         return $account->id === (int) $this->option('account');
        //     })->values();
        // }

        // return $accounts;
    }

    /**
     * Remove lock
     */
    public static function removeLock(): void
    {
        cache()->forget(static::LOCK_KEY);
    }

    /**
     * Set lock
     */
    public static function setLock(): void
    {
        cache([static::LOCK_KEY => true]);
    }

    /**
     * Checks whether the command is already running
     *
     * E.q. when trying to sync the mailbox manually via the front-end
     * can happen this command to already runs in background
     *
     * @return void
     *
     * @throws \App\MailClient\Exceptions\SynchronizationInProgressException
     */
    protected function checkIfAlreadyRunning(): void
    {
        if (! $this->option('manual')) {
            return;
        }

        throw_if(
            ! is_null(cache(static::LOCK_KEY)),
            SynchronizationInProgressException::class
        );
    }
}
