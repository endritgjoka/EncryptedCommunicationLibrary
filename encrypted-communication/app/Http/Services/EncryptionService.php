<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Crypt;

class EncryptionService
{
    /**
     * Encrypt the given key.
     *
     * @param string $key
     * @return string
     */
    public function encryptKey($key)
    {
        return Crypt::encryptString($key);
    }

    /**
     * Decrypt the given encrypted key.
     *
     * @param string $encryptedKey
     * @return string
     */
    public function decryptKey($encryptedKey)
    {
        return Crypt::decryptString($encryptedKey);
    }
}
