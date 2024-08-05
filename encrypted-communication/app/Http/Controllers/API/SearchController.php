<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\APIController;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class SearchController extends APIController
{
    function __invoke($query): JsonResponse
    {
        $authenticatedUserId = auth()->user()->id;

        $users = User::where('id','!=',$authenticatedUserId)->where('full_name', 'LIKE', "%$query%")->get();

        $users = $users->map(function ($user) use ($authenticatedUserId) {
            $conversation = Conversation::where('sender_id', $authenticatedUserId)
                    ->where('recipient_id', $user->id)
                    ->where('type', 'private')
                ->first();

            $lastMessage = null;
            if ($conversation) {
                $lastMessage = $conversation->lastMessage->message->load('user');
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

}
