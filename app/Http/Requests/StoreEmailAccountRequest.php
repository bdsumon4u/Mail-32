<?php

namespace App\Http\Requests;

use App\Enums\ConnectionType;
use App\Innoclapps\MailClient\ClientManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreEmailAccountRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'connection_type' => [Rule::requiredIf($this->isMethod('POST')), new Enum(ConnectionType::class)],
            'email' => $this->getEmailFieldRules(),
            'password' => $this->getRequiredIfRuleForImapField(),
            'sent_folder_id' => Rule::requiredIf($this->isMethod('PUT')),
            'trash_folder_id' => Rule::requiredIf($this->isMethod('PUT')),
            // 'from_name_header' => $this->getFromNameHeaderRules(), // HOTASH #
            'initial_sync_from' => $this->getInitialSyncFromRules(),
            'imap_server' => ['max:191', $this->getRequiredIfRuleForImapField()],
            'imap_port' => [$this->getRequiredIfRuleForImapField()/** 'numeric' */],
            'imap_encryption' => ['nullable', Rule::in(ClientManager::ENCRYPTION_TYPES)],
            'smtp_server' => ['max:191', $this->getRequiredIfRuleForImapField()],
            'smtp_port' => [$this->getRequiredIfRuleForImapField()/** 'numeric' */],
            'smtp_encryption' => ['nullable', Rule::in(ClientManager::ENCRYPTION_TYPES)],
            'validate_cert' => 'boolean|nullable',
            'folders' => $this->exists('folders') ? ['array', $this->getRequiredIfRuleForImapField()] : 'nullable',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        // $validator->after(function ($validator) {
        //     if ($this->isImapConnectionType() && ! (new RequirementsChecker)->passes('imap')) {
        //         abort(409, 'In order to use IMAP account type, you will need to enable the PHP extension "imap".');
        //     }

        //     if ($this->isMethod('POST') && $this->isSharedAccountRequest() && ! $this->user()->isSuperAdmin()) {
        //         abort(403, 'Only super administrators can create shared email accounts.');
        //     }
        // });
    }

    /**
     * Get the email field validation rules
     *
     * @return array
     */
    protected function getEmailFieldRules()
    {
        if ($this->isMethod('PUT') || ! $this->isImapConnectionType()) {
            return ['nullable'];
        }

        return  [
            'required',
            'email',
            'max:191',
            // UniqueRule::make(EmailAccount::class, 'account'), // HOTASH #
        ];
    }

    /**
     * Get the form_name_header field rule
     * NOTE: from_name_header field is only for shared email accounts
     *
     * @return Rule
     */
    protected function getFromNameHeaderRules()
    {
        return Rule::requiredIf(function () {
            if ($this->isMethod('POST') && $this->isSharedAccountRequest()) {
                return true;
            } elseif ($this->isMethod('PUT')) {
                $account = resolve(EmailAccountRepository::class)->find($this->route('account'));

                return $account->isShared();
            }

            return false;
        });
    }

    /**
     * Get the intial_sync_period field rule
     *
     * @return Rule
     */
    protected function getInitialSyncFromRules()
    {
        return [
            // 'date', // HOTASH #
            function ($attribute, $value, $fail) {
                // API Usage
                if ($value && strtotime($value) < strtotime('-6 months')) {
                    $fail('The initial synchronization date must not be older then 6 months.');
                }
            },
            Rule::requiredIf($this->isMethod('POST')),
        ];
    }

    /**
     * Get the requiredIf rule for the IMAP connection type fields
     *
     * @return \Illuminate\Validation\Rules\RequiredIf
     */
    protected function getRequiredIfRuleForImapField()
    {
        return Rule::requiredIf($this->isImapConnectionType());
    }

    /**
     * Check whether the account uses IMAP connection
     *
     * @return bool
     */
    public function isImapConnectionType()
    {
        return $this->connection_type === ConnectionType::Imap->value;
    }

    /**
     * Check whether the request is for creation shared account
     *
     * @return bool
     */
    protected function isSharedAccountRequest()
    {
        return is_null($this->user_id);
    }
}
