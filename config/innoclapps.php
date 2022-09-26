<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Unique Identification Key
    |--------------------------------------------------------------------------
    |
    */
    'key' => env('IDENTIFICATION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Application Date Format
    |--------------------------------------------------------------------------
    |
    | Application date format, the value is used when performing formats for to
    | local date via the available formatters.
    |
    */

    'date_format' => 'F j, Y',

    /*
    |--------------------------------------------------------------------------
    | Application Time Format
    |--------------------------------------------------------------------------
    |
    | Application time format, the value is used when performing formats for to
    | local datetime via the available formatters.
    |
    */

    'time_format' => 'H:i:s',

    /*
    |--------------------------------------------------------------------------
    | Application Currency
    |--------------------------------------------------------------------------
    |
    | The application currency, is used on a specific features e.q. form groups
    |
    */
    'currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | User Repository
    |--------------------------------------------------------------------------
    |
    | Provide the user repository.
    |
    */
    'user_repository' => null,

    /*
    |--------------------------------------------------------------------------
    | reCaptcha configuration
    |--------------------------------------------------------------------------
    |
    | reCaptcha configuration to provide additional security.
    |
    */
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', null),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', null),
        'ignored_ips' => env('RECAPTCHA_IGNORED_IPS', []),
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft deletes config
    |--------------------------------------------------------------------------
    |
    */
    'soft_deletes' => [
        'prune_after' => env('PRUNE_TRASHED_RECORDS_AFTER', 30), // in days
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail client configuration
    |--------------------------------------------------------------------------
    |
    | Below, you can find some of the mail client configuration options
    |
    */

    'mail_client' => [
        'reply_prefix' => env('MAIL_MESSAGE_REPLY_PREFIX', 'RE: '),
        'forward_prefix' => env('MAIL_MESSAGE_FORWARD_PREFIX', 'FW: '),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailable templates configuration
    |--------------------------------------------------------------------------
    |
    | layout => The mailable templates default layout view name
    |
    */

    'mailables' => [
        'layout' => env('MAILABLE_TEMPLATE_LAYOUT', 'mail.layout'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can specificy the default directory where the media files
    | will be uploaded, keep in mind that the application will create
    | folder tree in this directory according to custom logic e.q.
    | /media/contacts/:id/image.jpg
    |
    */
    'media' => [
        'directory' => env('MEDIA_DIRECTORY', 'media'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application favourite colors
    |--------------------------------------------------------------------------
    |
    */
    'colors' => explode(',', env(
        'COMMON_COLORS',
        '#374151,#DC2626,#F59E0B,#10B981,#2563EB,#4F46E5,#7C3AED,#EC4899,#F3F4F6'
    )),

    /*
    |--------------------------------------------------------------------------
    | Application actions config
    |--------------------------------------------------------------------------
    |
    */
    'actions' => [
        'disable_notifications_when_records_are_more_than' => env('DISABLE_ACTIONS_NOTIFICATIONS_WHEN_RECORDS_ARE_MORE_THAN', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application oAuth config
    |--------------------------------------------------------------------------
    |
    */
    'oauth' => [
        'state' => [
            /**
             * State storage driver
             */
            'storage' => 'session',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Microsoft Integration
    |--------------------------------------------------------------------------
    |
    | Microsoft integration related config for connecting via oAuth
    |
    */
    'microsoft' => [

        /**
         * The Microsoft Azure Application (client) ID
         *
         * https://portal.azure.com
         */
        'client_id' => env('MICROSOFT_CLIENT_ID'),

        /**
         * Azure application secret key
         */
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),

        /**
         * Application tenant ID
         * Use 'common' to support personal and work/school accounts
         */
        'tenant_id' => env('MICROSOFT_TENANT_ID', 'common'),

        /*
    * Set the url to trigger the OAuth process this url should call return Microsoft::connect();
    */
        'redirect_uri' => ENV('MICROSOFT_REDIRECT_URI', '/microsoft/callback'),

        /**
         * Login base URL
         */
        'login_url_base' => env('MICROSOFT_LOGIN_URL_BASE', 'https://login.microsoftonline.com'),

        /**
         * OAuth2 path
         */
        'oauth2_path' => env('MICROSOFT_OAUTH2_PATH', '/oauth2/v2.0'),

        /**
         * Microsoft scopes to be used, Graph API will acept up to 20 scopes
         *
         * @see https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-permissions-and-consent
         */
        'scopes' => [
            'offline_access',
            'openid',
            'User.Read',
            'Mail.ReadWrite',
            'Mail.Send',
            'MailboxSettings.ReadWrite',
            'Calendars.ReadWrite',
        ],

        /**
         * The default timezone is always set to UTC.
         */
        'prefer_timezone' => env('MS_GRAPH_PREFER_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Google Integration
    |--------------------------------------------------------------------------
    |
    | Google integration related config for connecting via oAuth
    |
    */
    'google' => [
        /**
         * Google Project Client ID
         */
        'client_id' => env('GOOGLE_CLIENT_ID'),

        /**
         * Google Project Client Secret
         */
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),

        /**
         * Callback URL
         */
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', '/google/callback'),

        /**
         * Access type
         */
        'access_type' => 'offline',

        /**
         * Scopes for OAuth
         */
        'scopes' => ['https://mail.google.com/', 'https://www.googleapis.com/auth/calendar'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application translation
    |--------------------------------------------------------------------------
    |
    | Define json path for the generated application language json file
    |
    */
    'lang' => [
        /**
         * The file path where the JSON generator will generate the translations
         */
        'json' => storage_path('i18n-locales.js'),

        /**
         * The translator custom path for OverrideFileLoader
         */
        'custom' => lang_path('.custom'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Define default VoIP Client
    |--------------------------------------------------------------------------
    |
    | Currently only "Twilio" is supported.
    |
    */

    'voip' => [
        'client' => env('VOIP_CLIENT'),
        // Route names
        'endpoints' => [
            'call' => 'voip.call',
            'events' => 'voip.events',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | The application available services
    |--------------------------------------------------------------------------
    |
    | Here may be defined available services for the Innoclaps namespace
    |
    */
    'services' => [
        'twilio' => [
            'applicationSid' => env('TWILIO_APP_SID'),
            'accountSid' => env('TWILIO_ACCOUNT_SID'),
            'authToken' => env('TWILIO_AUTH_TOKEN'),
            'number' => env('TWILIO_NUMBER'),
        ],
    ],

    'resources' => [
        'permissions' => [
            'common' => null,
        ],
    ],
];
