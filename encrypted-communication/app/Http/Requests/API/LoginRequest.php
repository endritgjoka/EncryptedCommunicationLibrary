<?php

namespace App\Http\Requests\API;

use App\Http\Requests\APIRequest;

class LoginRequest extends APIRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required',
            'password' => 'required|string',
        ];
    }
}
