<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\APIController;
use App\Http\Requests\MessageRequest;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Message;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;

class ChatController extends APIController
{
    function sendMessage(MessageRequest $request, $recipient): JsonResponse
    {
        $authenticatedUserId = auth()->user()->id;

        $recipientUser = User::find($recipient);
        if (!$recipientUser || $recipient == $authenticatedUserId) {
            return $this->respondWithSuccess(null, __('app.user.not_found'), 404);
        }

        $data = $request->validated();

        $conversation1 = Conversation::firstOrCreate(
            ['sender_id' => $authenticatedUserId, 'recipient_id' => $recipient, 'type' => 'private'],
            ['unread_messages' => 0]
        );

        $conversation2 = Conversation::firstOrCreate(
            ['sender_id' => $recipient, 'recipient_id' => $authenticatedUserId, 'type' => 'private'],
            ['unread_messages' => 0]
        );

        $messageContent = $data['message'];
        $encryptionKey = $this->generateEncryptionKey();

        try {
            $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $encryptedContent = openssl_encrypt($messageContent, 'aes-256-cbc', base64_decode($encryptionKey), 0, $iv);

            if ($encryptedContent === false) {
                throw new Exception("Encryption failed");
            }

            $message = Message::create([
                'user_id' => $authenticatedUserId,
                'encryption_key' => $encryptionKey,
                'encrypted_content' => $encryptedContent,
                'iv' => base64_encode($iv),
            ]);

            ConversationMessage::create([
                'conversation_id' => $conversation1->id,
                'message_id' => $message->id
            ]);
            ConversationMessage::create([
                'conversation_id' => $conversation2->id,
                'message_id' => $message->id
            ]);

            // Increment unread messages for the recipient
            if ($conversation1->sender_id == $recipient) {
                $conversation1->increment('unread_messages');
            } else {
                $conversation2->increment('unread_messages');
            }

            // broadcast(new MessageEvent($message, $recipient, $authenticatedUserId, 'private'))->toOthers();
            // broadcast(new MessageEvent($message, $authenticatedUserId, $recipient, 'private'))->toOthers();

            return $this->respondWithSuccess($message, __('app.message.success'));
        } catch (Exception $e) {
            return $this->respondWithError("Encryption error: " . $e->getMessage(), __('app.message.failed'));
        }
    }

    // AES-256 (256-bit key) generator
    function generateEncryptionKey()
    {
        return base64_encode(random_bytes(32));
    }


    function getMessages($recipient): JsonResponse
    {
        $authUserId = auth()->user()->id;
        $conversation = Conversation::where('sender_id', $authUserId)
            ->where('recipient_id', $recipient)->first();
        if (!$conversation) {
            return $this->respondWithSuccess(null, __('app.conversation.not_found'), 404);
        }
        $conversationId = $conversation->id;
        $messages = Message::whereHas('conversations', function ($query) use ($conversationId) {
            $query->where('conversation_id', $conversationId);
        })->with('user')
            ->get();

        return $this->respondWithSuccess($messages, __('app.message.success'));

    }

    function getConversations(): JsonResponse
    {
        $user = auth()->user();
        $conversations = Conversation::where('sender_id', $user->id)
            ->where('type', 'private')
            ->with(['lastMessage.message.user'])
            ->get()
            ->map(function ($conversation) {
                if ($conversation->lastMessage) {
                    $conversation->message = $conversation->lastMessage->message;
                    unset($conversation->lastMessage);
                }
                return $conversation;
            });

        return $this->respondWithSuccess($conversations, __('app.conversations.success'));
    }


}
