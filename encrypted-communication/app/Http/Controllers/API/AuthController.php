<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\APIController;
use App\Http\Requests\API\LoginRequest;
use App\Http\Requests\API\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthController extends APIController
{

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return $this->respondWithError(__('auth.failed'), __('app.login.failed'));
        }

        $response = [
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user,
        ];

        return $this->respondWithSuccess($response, __('app.login.success'));
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $existingUser = User::where('email', $data['email'])
            ->first();

        if($existingUser){
            return $this->respondWithError(__('auth.failed'), __('app.login.user_exists'));
        }

        $userData = User::create($data);

        return $this->respondWithSuccess(UserResource::make($userData));
    }

}
