<?php

namespace App\Http\Requests;

use App\Traits\APIResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class APIRequest extends FormRequest
{
    use APIResponse;

    protected function failedValidation(Validator $validator)
    {
        $messages = $validator->errors()->getMessageBag()->all();

        $messageTitle = collect($messages)->implode(' ');

        throw new ValidationException($validator,
            $this->respondWithError($validator->errors(), $messageTitle, 422)
        );
    }
}
