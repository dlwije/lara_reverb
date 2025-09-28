<?php

namespace App\Actions\Fortify;

use App\Models\Sma\People\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

//        return User::create([
//            'name' => $input['name'],
//            'email' => $input['email'],
//            'password' => Hash::make($input['password']),
//        ]);
        $user = DB::transaction(function () use ($input) {
            $user = User::create([
                'name'     => $input['name'],
                'email'    => $input['email'],
                'username' => $input['username'],
                'password' => Hash::make($input['password']),
                'active'   => true,
                'employee' => false,
            ]);

            $settings = get_settings(['default_price_group', 'default_customer_group']);
            $customer = Customer::create([
                'user_id'           => $user->id,
                'name'              => $input['name'],
                'email'             => $input['email'],
                'company'           => $input['company'],
                'price_group_id'    => $settings['default_price_group'] ?? null,
                'customer_group_id' => $settings['default_customer_group'] ?? null,
            ]);

            $user->assignRole('Customer');
            $user->customer_id = $customer->id;
            $user->saveQuietly();

            return $user;
        });

        return $user;
    }
}
