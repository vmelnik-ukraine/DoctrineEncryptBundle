<?php

namespace VMelnik\DoctrineEncryptBundle\Tests;

use PHPUnit\Framework\TestCase;
use VMelnik\DoctrineEncryptBundle\Encryptors\AES256Encryptor;

/**
 * Class AES256EncryptorTest.
 */
class AES256EncryptorTest extends TestCase
{
    const SECRET_KEY = '624758dbe24e20067b27fc3cef22dc61';

    /**
     * @test
     */
    public function checkEncryptor()
    {
        $aes = new AES256Encryptor(self::SECRET_KEY);

        $data = 'Content some data!';
        $encryptData = $aes->encrypt($data);

        self::assertEquals($data, $aes->decrypt($encryptData));
    }
}
