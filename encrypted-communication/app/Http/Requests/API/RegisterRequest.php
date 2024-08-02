<?php

namespace App\Http\Requests\API;

use App\Http\Requests\APIRequest;

class RegisterRequest extends APIRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed'
        ];
    }
}
