<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class MessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'message' => 'required|string',
        ];
    }
}
