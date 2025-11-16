<?php

namespace Modules\ApiAuth\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Role::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $permissions = Permission::all();

        $roleAdminApi = Role::create(['name' => 'sys_admin', 'guard_name' => 'api']);
        Role::create(['name' => 'sys_subadmin', 'guard_name' => 'api']);
        Role::create(['name' => 'agent_admin', 'guard_name' => 'api']);
        Role::create(['name' => 'agent_subadmin', 'guard_name' => 'api']);
        Role::create(['name' => 'dealer', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'api']);

        $roleAdminWeb = Role::create(['name' => 'sys_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'sys_subadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'agent_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'agent_subadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'dealer', 'guard_name' => 'web']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);


        $roleAdminApi->syncPermissions($permissions);
//        $roleAdminWeb->syncPermissions($permissions);
    }
}
