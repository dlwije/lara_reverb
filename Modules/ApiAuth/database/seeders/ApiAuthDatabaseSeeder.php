<?php

namespace Modules\ApiAuth\Database\Seeders;

//use Database\Seeders\PermissionTableSeeder;
//use Database\Seeders\RoleSeeder;
//use Database\Seeders\UserSeeder;
use Illuminate\Database\Seeder;

class ApiAuthDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->call([]);
        $this->call(PermissionTableSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
    }
}
