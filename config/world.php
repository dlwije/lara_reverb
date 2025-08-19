<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed countries to be loaded
    | Leave it empty to load all countries else include the country iso2
    | value in the allowed_countries array
    |--------------------------------------------------------------------------
    */
    'allowed_countries' => [],

    /*
    |--------------------------------------------------------------------------
    | Disallowed countries to not be loaded
    | Leave it empty to allow all countries to be loaded else include the
    | country iso2 value in the disallowed_countries array
    |--------------------------------------------------------------------------
    */
    'disallowed_countries' => [],

    /*
    |--------------------------------------------------------------------------
    | Supported locales.
    |--------------------------------------------------------------------------
    */
    'accepted_locales' => [
        'ar',
        'bn',
        'br',
        'de',
        'en',
        'es',
        'fa',
        'fr',
        'hr',
        'it',
        'ja',
        'kr',
        'nl',
        'pl',
        'pt',
        'ro',
        'ru',
        'tr',
        'zh',
    ],
    /*
    |--------------------------------------------------------------------------
    | Enabled modules.
    | The cities module depends on the states module.
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'states'     => true,
        'cities'     => true,
        'timezones'  => true,
        'currencies' => true,
        'languages'  => false,
    ],
    /*
    |--------------------------------------------------------------------------
    | Routes.
    |--------------------------------------------------------------------------
    */
    'routes' => true,
    /*
    |--------------------------------------------------------------------------
    | Connection.
    |--------------------------------------------------------------------------
    */
    'connection' => env('WORLD_DB_CONNECTION', env('DB_CONNECTION')),
    /*
    |--------------------------------------------------------------------------
    | Migrations.
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'countries' => [
            'table_name'      => 'countries',
            'optional_fields' => [
                'phone_code' => [
                    'required' => true,
                    'type'     => 'string',
                    'length'   => 5,
                ],
                'iso3' => [
                    'required' => true,
                    'type'     => 'string',
                    'length'   => 3,
                ],
                'native' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'region' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'subregion' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'latitude' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'longitude' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'emoji' => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'emojiU' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ],
        'states' => [
            'table_name'      => 'states',
            'optional_fields' => [
                'country_code' => [
                    'required' => true,
                    'type'     => 'string',
                    'length'   => 3,
                ],
                'state_code' => [
                    'required' => true,
                    'type'     => 'string',
                    'length'   => 5,
                ],
                'type' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'latitude' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'longitude' => [
                    'required' => false,
                    'type'     => 'string',
                ],
            ],
        ],
        'cities' => [
            'table_name'      => 'cities',
            'optional_fields' => [
                'country_code' => [
                    'required' => true,
                    'type'     => 'string',
                    'length'   => 3,
                ],
                'state_code' => [
                    'required' => true,
                    'type'     => 'string',
                    'length'   => 5,
                ],
                'latitude' => [
                    'required' => false,
                    'type'     => 'string',
                ],
                'longitude' => [
                    'required' => false,
                    'type'     => 'string',
                ],
            ],
        ],
        'timezones' => [
            'table_name' => 'timezones',
        ],
        'currencies' => [
            'table_name' => 'currencies',
        ],
        'languages' => [
            'table_name' => 'languages',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fully qualified class names for package models.
    | You can extend package models with your custom ones.
    |--------------------------------------------------------------------------
    */

    'models' => [
        'states'     => App\Models\State::class,
        'countries'  => App\Models\Country::class,
        'timezones'  => App\Models\Timezone::class,
        'cities'     => Nnjeim\World\Models\City::class,
        'currencies' => Nnjeim\World\Models\Currency::class,
        'languages'  => Nnjeim\World\Models\Language::class,
    ],
];
