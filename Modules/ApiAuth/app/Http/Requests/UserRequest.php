<?php

namespace Modules\ApiAuth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id') ?? null;
        return [
            'name'     => 'required|max:50',
            'phone' => 'nullable|string|regex:/^\+?[0-9]{10,14}$/|unique:users,phone,' . $userId,
//            'phone'    => 'nullable|unique:users,email,' . $this->route('user')?->id,
            'email'    => 'required|email|unique:users,email,' . $userId,
//            'username' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username,' . $this->route('user')?->id],
            'team_id' => 'nullable|exists:teams,id', // Ensure the teams exists
            'roles' => 'nullable|exists:roles,id', // Ensure the teams exists
            'password' => [
                $this->route('user')?->id ? 'nullable' : 'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols(),
//                    ->uncompromised(), // Checks password is not in data breaches
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validated data from the request.
     *
     * @param  array|int|string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($data['password'] ?? null) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }
}
