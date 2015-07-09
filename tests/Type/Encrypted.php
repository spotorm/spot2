<?php
namespace SpotTest\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class Encrypted extends Type
{
    public static $key;

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (is_string($value)) {
            $value = self::aes256_decrypt(self::$key, base64_decode($value));
        } else {
            $value = null;
        }

        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return base64_encode(self::aes256_encrypt(self::$key, $value));
    }

    public function getName()
    {
        return 'encrypted';
    }

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'TEXT';
    }

    private function aes256_encrypt($key, $data)
    {
      if(32 !== strlen($key)) $key = hash('SHA256', $key, true);
      $padding = 16 - (strlen($data) % 16);
      $data .= str_repeat(chr($padding), $padding);

      return mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, str_repeat("\0", 16));
    }

    private function aes256_decrypt($key, $data)
    {
      if(32 !== strlen($key)) $key = hash('SHA256', $key, true);
      $data = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, str_repeat("\0", 16));
      $padding = ord($data[strlen($data) - 1]);

      return substr($data, 0, -$padding);
    }
}
