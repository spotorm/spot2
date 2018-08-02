<?php
namespace SpotTest\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class Encrypted extends Type
{
    public static $key;
    private static $hashing = 'SHA256';
    private static $cipher = 'AES-128-CBC';

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (is_string($value)) {
            $value = self::aes256_decrypt(self::$key, self::$hashing, self::$cipher, base64_decode($value));
        } else {
            $value = null;
        }

        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return base64_encode(self::aes256_encrypt(self::$key, self::$hashing, self::$cipher, $value));
    }

    public function getName()
    {
        return 'encrypted';
    }

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'TEXT';
    }

    private function aes256_encrypt($key, $hashing, $cipher, $data)
    {
        if(32 !== strlen($key)) $key = hash($hashing, $key, true);
      
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($data, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac($hashing, $ciphertext_raw, $key, $as_binary = true);
        
        return $iv.$hmac.$ciphertext_raw;
    }

    private function aes256_decrypt($key, $hashing, $cipher, $data)
    {
        if(32 !== strlen($key)) $key = hash($hashing, $key, true);
        
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($data, 0, $ivlen);
        $hmac = substr($data, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($data, $ivlen + $sha2len);
        $original = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac($hashing, $ciphertext_raw, $key, $as_binary = true);
        
        if (hash_equals($hmac, $calcmac)) { // PHP 5.6+ timing attack safe comparison
            return $original;
        } else {
            throw new \RuntimeException("Timing attack safe string comparison failed.");
        }
    }
}
