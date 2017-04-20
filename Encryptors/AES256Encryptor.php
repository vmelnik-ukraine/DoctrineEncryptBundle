<?php

namespace VMelnik\DoctrineEncryptBundle\Encryptors;

/**
 * Class for AES256 encryption.
 *
 * @author Victor Melnik <melnikvictorl@gmail.com>
 */
class AES256Encryptor implements EncryptorInterface
{
    const ENCRYPT_METHOD = 'aes-256-ecb';

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var string
     */
    private $initializationVector;

    /**
     * {@inheritdoc}
     */
    public function __construct($key)
    {
        $this->secretKey = md5($key);
        $this->initializationVector = openssl_random_pseudo_bytes(
            openssl_cipher_iv_length(self::ENCRYPT_METHOD)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt($data)
    {
        return trim(base64_encode(
            openssl_encrypt($data, self::ENCRYPT_METHOD, $this->secretKey, 0, $this->initializationVector))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt($data)
    {
        return trim(
            openssl_decrypt(base64_decode($data), self::ENCRYPT_METHOD, $this->secretKey, 0, $this->initializationVector)
        );
    }
}
