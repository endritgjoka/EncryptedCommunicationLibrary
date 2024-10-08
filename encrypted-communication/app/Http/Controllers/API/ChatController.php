<?php

namespace App\Http\Controllers\API;

use App\Events\MessageEvent;
use App\Http\Controllers\APIController;
use App\Http\Requests\API\MessageRequest;
use App\Http\Services\EncryptionService;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Message;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Scalar\Int_;

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
        $generatedKey = $this->generateEncryptionKey(); // 128, 192, or 256 bits
        $cipher = 'aes-256-cbc'; //'aes-128-cbc', 'aes-192-cbc', or 'aes-256-cbc'
//        Log::info('Key: ' . $generatedKey);
        try {
            $iv = random_bytes(openssl_cipher_iv_length($cipher));
            $encryptedContent = openssl_encrypt($messageContent, $cipher, base64_decode($generatedKey), 0, $iv);

            if ($encryptedContent === false) {
                throw new Exception("Encryption failed");
            }
            $encryptedKey = $this->encryptKey(base64_encode($generatedKey));

            $message = Message::create([
                'user_id' => $authenticatedUserId,
                'encryption_key' => $encryptedKey,
                'encrypted_content' => $encryptedContent,
                'iv' => base64_encode($iv),
            ]);
            $message->load('user');
//            $message = $message->toArray();
            unset($message->encryption_key);  // Remove the 'encryption_key' field

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

            //$recipient should be an integer
            $recipientId = is_object($recipient) ? $recipient->id : intval($recipient);
            $recipientUser = User::find($recipient);

            broadcast(new MessageEvent($message, $recipientId, $authenticatedUserId,auth()->user()->full_name, 'private', $generatedKey))->toOthers();
            broadcast(new MessageEvent($message, $authenticatedUserId, $recipientId, $recipientUser->full_name,'private', $generatedKey))->toOthers();

//            Log::info('Broadcast event:', ['message' => $message]);

            return $this->respondWithSuccess($message, __('app.message.success'));
        } catch (Exception $e) {
            return $this->respondWithError("Encryption error: " . $e->getMessage(), __('app.message.failed'));
        }
    }

    // AES-256 (256-bit key) generator
    function generateEncryptionKey($bits = 256)
    {
        return base64_encode(random_bytes($bits / 8));
    }


    function getMessages($recipient): JsonResponse
    {
        $authUserId = auth()->user()->id;
        $conversation = Conversation::where('sender_id', $authUserId)
            ->where('recipient_id', $recipient)->first();

        if (!$conversation) {
            return $this->respondWithSuccess([], __('app.conversation.empty'), 200);
        }

        $conversationId = $conversation->id;
        $messages = Message::whereHas('conversations', function ($query) use ($conversationId) {
            $query->where('conversation_id', $conversationId);
        })->with('user')->get();

        foreach ($messages as $message) {
            $message->encryption_key = base64_decode($this->decryptKey($message->encryption_key));
        }

        return $this->respondWithSuccess($messages, __('app.message.success'));
    }


    function getConversations(): JsonResponse
    {
        $user = auth()->user();
        $conversations = Conversation::where('sender_id', $user->id)
            ->where('type', 'private')
            ->with('recipient')
            ->with(['lastMessage.message.user'])
            ->get()
            ->map(function ($conversation) {
                $lastMessage = null;
                if ($conversation && $conversation->lastMessage) {
                    $lastMessage = $conversation->lastMessage->message->load('user');
                    if ($lastMessage) {
                        $lastMessage->encryption_key = base64_decode($this->decryptKey($lastMessage->encryption_key));
                    }
                }

                $recipient = $conversation->recipient;
                unset($conversation->lastMessage);
                unset($conversation->recipient);

                return [
                    'conversation' => $conversation,
                    'last_message' => $lastMessage,
                    'recipient' => $recipient
                ];
            })
            ->sortByDesc(function ($conversation) {
                return $conversation['last_message'] ? $conversation['last_message']->created_at : null;
            });

        return $this->respondWithSuccess($conversations->values()->all(), __('app.conversations.success'));
    }


    function markConversationAsRead($recipient): JsonResponse
    {
        $authenticatedUserId = auth()->user()->id;

        $conversation = Conversation::where('sender_id', $authenticatedUserId)
            ->where('recipient_id', $recipient)
            ->first();
        if (!$conversation) {
            return $this->respondWithError(null, __('app.conversation.not_found'), 404);
        }
        $conversation->unread_messages = 0;
        $conversation->update();

        return $this->respondWithSuccess($conversation, __('app.conversation_read.success'));
    }

    public function encryptKey($key)
    {
        $encryptedKey = Crypt::encryptString($key);
        Log::info('Encrypted Key Length: ' . strlen($encryptedKey));
        return $encryptedKey;
    }

    public function decryptKey($encryptedKey)
    {
        $key = Crypt::decryptString($encryptedKey);
        Log::info('Decrypted Key Length: ' . strlen($key));
        return $key;
    }


}
