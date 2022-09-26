<?php

namespace App\Http\Controllers;

use App\Enums\ConnectionType;
use App\Innoclapps\MailClient\ClientManager;
use App\Innoclapps\MailClient\Exceptions\ConnectionErrorException;
use App\Innoclapps\MailClient\FolderCollection;
use App\Innoclapps\MailClient\Imap\ImapConfig;
use App\Innoclapps\MailClient\Imap\SmtpConfig;
use Illuminate\Contracts\Validation\Validator as ValidationContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmailAccountConnectionTestController extends Controller
{
    protected FolderCollection $imapFolders;

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // if (! (new RequirementsChecker)->passes('imap')) {
        //     abort(409, 'In order to use IMAP account type, you will need to enable the PHP extension "imap".');
        // }

        $validator = Validator::make($request->all(), [
            'connection_type' => 'required:in:'.ConnectionType::Imap->value,
            'email' => 'required|email|max:191',
            'password' => $request->has('id') ? 'nullable' : 'required',
            'imap_server' => 'required|string|max:191',
            'imap_port' => 'required|numeric',
            'imap_encryption' => ['nullable', Rule::in(ClientManager::ENCRYPTION_TYPES)],
            'smtp_server' => 'required|string|max:191',
            'smtp_port' => 'required|numeric',
            'smtp_encryption' => ['nullable', Rule::in(ClientManager::ENCRYPTION_TYPES)],
        ]);

        $validator->after(function ($validator) use ($request) {
            // Validation passes, now we can validate the connections
            $this->testConnection(
                $validator,
                [
                    'username' => $request->input('username'),
                    'validate_cert' => $request->input('validate_cert'),
                    'email' => $request->input('email'),
                    'password' => $this->getPassword($request),
                    'imap_server' => $request->input('imap_server'),
                    'imap_port' => $request->input('imap_port'),
                    'imap_encryption' => $request->input('imap_encryption'),
                ],
                [
                    'username' => $request->input('username'),
                    'validate_cert' => $request->input('validate_cert'),
                    'email' => $request->input('email'),
                    'password' => $this->getPassword($request),
                    'smtp_server' => $request->input('smtp_server'),
                    'smtp_port' => $request->input('smtp_port'),
                    'smtp_encryption' => $request->input('smtp_encryption'),
                ]
            );
        });

        $validator->validate();

        // return $this->imapFolders;

        return $this->response(['folders' => $this->imapFolders]);
    }

    /**
     * Determine which password should be used for the test configuration
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getPassword(Request $request)
    {
        $account = $request->input('id') ? resolve(EmailAccountRepository::class) // HOTASH #
            ->find($request->input('id')) : false;

        if (! $account) {
            return $request->input('password');
        }

        // User inputted password for testing
        if ($password = $request->input('password')) {
            return $password;
        }

        return $account->password;
    }

    /**
     * Test the actual connection after all validation passes
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  array  $imapConfig
     * @param  array  $smtpConfig
     * @return void
     */
    protected function testConnection(ValidationContract $validator, array $imapConfig, array $smtpConfig)
    {
        if ($validator->errors()->isEmpty()) {
            try {
                $client = ClientManager::createSmtpClient(new SmtpConfig(
                    $smtpConfig['smtp_server'],
                    $smtpConfig['smtp_port'],
                    $smtpConfig['smtp_encryption'],
                    $smtpConfig['email'],
                    $smtpConfig['validate_cert'],
                    $smtpConfig['username'],
                    $smtpConfig['password']
                ));

                ClientManager::testConnection($client);
            } catch (ConnectionErrorException $e) {
                $validator->errors()->add('smtp-connection', 'SMTP: '.$e->getMessage());
            }

            try {
                $client = ClientManager::createImapClient(new ImapConfig(
                    $imapConfig['imap_server'],
                    $imapConfig['imap_port'],
                    $imapConfig['imap_encryption'],
                    $imapConfig['email'],
                    $imapConfig['validate_cert'],
                    $imapConfig['username'],
                    $imapConfig['password']
                ));

                ClientManager::testConnection($client);

                $this->imapFolders = $client->getFolders();
            } catch (ConnectionErrorException $e) {
                $validator->errors()->add('imap-connection', 'IMAP: '.$e->getMessage());
            }
        }
    }

    /**
     * General API Response
     *
     * @see  \Illuminate\Contracts\Routing\ResponseFactory
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function response($data = [], $status = 200, array $headers = [], $options = 0)
    {
        // https://stackoverflow.com/questions/57604784/laravel-resource-collection-paginate-json-response-error - for paginated collections
        if ($data instanceof AnonymousResourceCollection) {
            $data = $data->toResponse(Request::instance())->getData();
        }

        return response()->json($data, $status, $headers, $options);
    }
}
