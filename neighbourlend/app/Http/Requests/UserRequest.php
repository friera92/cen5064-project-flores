<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Ignore validation rules entirely for GET or DELETE requests
        if ($this->isMethod('get') || $this->isMethod('delete')) {
            return [];
        }

        $userId = $this->user()->id;

        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::defaults()],
            'phone'    => ['sometimes', 'nullable', 'string', 'max:20'],
            'address'  => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
