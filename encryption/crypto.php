<?php
// encryption/crypto.php

class Crypto {
    // Encryption key should be stored securely in an environment variable in production
    private static function getKey() {
        return getenv('ENCRYPTION_KEY') ?: 'CHATUS_SECURE_ENCRYPTION_KEY_2026!';
    }
    private const CIPHER_METHOD = 'AES-256-CBC';

    /**
     * Encrypt a string using AES-256-CBC
     * @param string $data The string to encrypt
     * @return string The encrypted string (base64 encoded with IV)
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return $data;
        }
        
        $key = hash('sha256', self::getKey());
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt($data, self::CIPHER_METHOD, $key, 0, $iv);
        
        // Return IV + encrypted string encoded as base64
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt an AES-256-CBC encrypted string
     * @param string $data The encrypted string (base64 encoded with IV)
     * @return string|false The decrypted string, or false on failure
     */
    public static function decrypt($data) {
        if (empty($data)) {
            return $data;
        }

        $key = hash('sha256', self::getKey());
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
        
        if (strlen($data) < $ivLength) {
            return false; // Malformed data
        }

        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, self::CIPHER_METHOD, $key, 0, $iv);
    }
}
?>
