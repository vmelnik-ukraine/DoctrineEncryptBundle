<?php

namespace VMelnik\DoctrineEncryptBundle\Encryptors;

/**
 * Encryptor interface for encryptors
 * 
 * @author Victor Melnik <melnikvictorl@gmail.com>
 */
interface EncryptorInterface {

    /**
     * Must accept secret key for encryption 
     */
    public function __construct($secretKey);

    /**
     * @param string $data Plain text to encrypt
     * @return string Encrypted text
     */
    public function encrypt($data);

    /**
     * @param string $data Encrypted text
     * @return string Plain text
     */
    public function decrypt($data);
}
