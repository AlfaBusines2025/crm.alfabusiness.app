<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'translation' => [
        // Microsoft translation
        // https://azure.microsoft.com/en-us/pricing/details/cognitive-services/translator-text-api/
        'microsoft' => [
            'enabled' => true,
            'name' => 'Microsoft Translator API',
            // subscription key
            'subscription_key' => env('MICROSOFT_TRANSLATE_API_KEY'),
        ],
    ],
	
	'pusher_beams' => [
        'instance_id' => env('PUSHER_BEAMS_INSTANCE_ID'),
        'secret_key' => env('PUSHER_BEAMS_SECRET_KEY'),
    ],
	
	'google' => [
		'client_id'     => env('GOOGLE_CLIENT_ID'),
		'client_secret' => env('GOOGLE_CLIENT_SECRET'),
		'redirect'      => env('GOOGLE_CALLBACK_URL'), // <- OJO
	],

];
