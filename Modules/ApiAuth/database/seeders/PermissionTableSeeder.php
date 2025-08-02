<?php

namespace Modules\ApiAuth\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Permission::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $permissions = [
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'user-management',
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            'product-list',
            'product-create',
            'product-edit',
            'product-delete',
            'category-list',
            'category-create',
            'category-edit',
            'category-delete',
            'attribute-list',
            'attribute-create',
            'attribute-edit',
            'attribute-delete',
            'attribute_option-list',
            'attribute_option-create',
            'attribute_option-edit',
            'attribute_option-delete',
            'slider-list',
            'slider-create',
            'slider-edit',
            'slider-delete',
            'coupon-list',
            'coupon-create',
            'coupon-edit',
            'coupon-delete',
            'inventory-list',
            'inventory-create',
            'inventory-edit',
            'inventory-delete',
            'address-list',
            'address-create',
            'address-edit',
            'address-delete',
            'country-list',
            'country-create',
            'country-edit',
            'country-delete',
            'state-list',
            'state-create',
            'state-edit',
            'state-delete',
            'city-list',
            'city-create',
            'city-edit',
            'city-delete',
            'report-list',
            'user_comment-list',
            'user_comment-create',
            'user_comment-edit',
            'user_comment-delete',
        ];

        foreach ($permissions as $permission) {
            // Extract the first part of the permission before the hyphen
            $prefix = Str::before($permission, '-');

            // Find the header ID from the permissionHead table
//            $header = PermissionHead::where('permission_title', $prefix)->first();

            Permission::firstOrCreate([
//                'header_id' => $header ? $header->id : 0, // Default to 0 if no match is found
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }
    }
}
