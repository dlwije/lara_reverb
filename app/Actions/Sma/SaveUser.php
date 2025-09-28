<?php

namespace App\Actions\Sma;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveUser
{
    /**
     * Save transfers with relationships
     *
     * @param  array<string, string>  $input
     * @param  User  $input
     */
    public function execute(array $data, User $user = new User): User
    {
//        $data['employee'] = true;
        $roles = $data['roles'] ?? [];
        $stores = $data['stores'] ?? [];
        $settings = $data['settings'] ?? [];

//        echo "<pre>";
//        print_r($data);exit();
        unset($data['roles'], $data['stores'], $data['settings']);

         $user->email_verified_at = now();

        DB::transaction(function () use ($data, $roles, $stores, $settings, &$user) {
            $user->fill($data)->save();

            $user->syncRoles($roles);
            $user->stores()->sync($stores);
            if (! empty($settings)) {
                $settings = collect($settings)->transform(fn ($item) => ['key' => $item['key'], 'value' => $item['value'], 'user_id' => $user->id, 'company_id' => $user->company_id])->toArray();
                $user->settings()->upsert($settings, uniqueBy: ['key', 'user_id'], update: ['value']);
            }
        });
        return $user;
    }
}
