<?php

namespace VMelnik\DoctrineEncryptBundle\Encryptors;

/**
 * Encryptor interface. Provides methods for encryptors
 */
interface EncryptorInterface {

    /**
     * Must accept secret key for encryption 
     */
    public function __construct($secretKey);

    /**
     * Must accept data and return encrypted data 
     */
    public function encrypt($data);

    /**
     * Must accept data and return decrypted data 
     */
    public function decrypt($data);
}