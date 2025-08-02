<?php

namespace Modules\ApiAuth\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Modules\ApiAuth\Actions\SaveUser;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()['cache']->forget('spatie.permission.cache');
        $service = app(SaveUser::class); // The class where `execute` lives

        $service->execute([
            'name' => 'System Admin',
            'email' => 'admin@taggo.ae',
            'password' => 'Welcome@321',
            'roles' => ['sys_admin'],
        ]);

//        $internalRequest = Request::create('/oauth/token', 'POST', [
//            'grant_type' => 'password',
//            'client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
//            'client_secret' => env('PASSPORT_PASSWORD_SECRET'),
//            'username' => 'admin@taggo.ae',
//            'password' => 'Welcome@321',
//            'scope' => '', // add scopes if needed
//        ]);
//
//        // Dispatch internally
//        $response = app()->handle($internalRequest);
    }
}
