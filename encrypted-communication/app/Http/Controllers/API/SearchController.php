<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\APIController;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SearchController extends APIController
{
    function __invoke($query): JsonResponse
    {
        $authenticatedUserId = auth()->user()->id;

        $users = User::where('id', '!=', $authenticatedUserId)
            ->where('full_name', 'LIKE', "%$query%")
            ->get();

        $users = $users->map(function ($user) use ($authenticatedUserId) {
            $conversation = Conversation::where('sender_id', $authenticatedUserId)
                ->where('recipient_id', $user->id)
                ->where('type', 'private')
                ->first();

            $lastMessage = null;
            if ($conversation && $conversation->lastMessage) {
                $lastMessage = $conversation->lastMessage;

                if ($lastMessage->message) {
                    $lastMessage = $lastMessage->message;
                    $lastMessage->load('user');
                    $lastMessage->encryption_key = base64_decode($this->decryptKey($lastMessage->encryption_key));
                } else {
                    $lastMessage = null;
                }

                unset($conversation->lastMessage);
            }

            return [
                'conversation' => $conversation,
                'last_message' => $lastMessage,
                'recipient' => $user
            ];
        });

        return $this->respondWithSuccess($users, __('app.search.success'));
    }


    public function decryptKey($encryptedKey)
    {
        $key = Crypt::decryptString($encryptedKey);
        return $key;
    }

}
