<?php

namespace App\Http\Requests\Sma\People;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->route('user')) {
            if ($this->user()->id == $this->route('user')->id) {
                throw ValidationException::withMessages([__('You are not allowed to update yourself. Please use profile to update your account OR login with different user to update this account.')]);
            }

            return $this->user()->can('update-users');
        }

        return $this->user()->can('create-users');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => 'required|max:50',
            'phone'    => 'nullable|unique:users,email,' . $this->route('user')?->id,
            'email'    => 'required|email|unique:users,email,' . $this->route('user')?->id,
            'username' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username,' . $this->route('user')?->id],
            'password' => $this->route('user')?->id ? 'nullable|confirmed|min:8' : 'required|min:8',

            'stores'              => 'nullable|array',
            'settings'            => 'nullable|array',
            'active'              => 'nullable|boolean',
            'read-all'            => 'nullable|boolean',
            'edit_all'            => 'nullable|boolean',
            'bulk_actions'        => 'nullable|boolean',
            'employee'            => 'nullable|boolean',
            'can_be_impersonated' => 'nullable|boolean',
            'store_id'            => 'nullable|exists:stores,id',
            'supplier_id'         => 'nullable|exists:suppliers,id',
            'customer_id'         => 'nullable|exists:customers,id',
            'roles'               => 'required_without:customer_id|array',

//            'extra_attributes' => ['nullable', new ExtraAttributes('user')],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($data['password'] ?? null) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($this->employee && ! $this->route('user')?->id) {
            $data['settings']['number'] = $data['settings']['number'] ?? 'E' . (User::where('employee', 1)->count() + 1);
        }

        return $data;
    }
}
