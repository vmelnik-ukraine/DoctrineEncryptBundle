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
     * Must accept data and return encrypted data 
     * @param string $data The data to be encrypted
     * @param bool $deterministic Should the data use a shared key or should the 
     * key be determined individually for each data element.  TRUE - Use the 
     * same key.  FALSE - Use a new key for each.
     */
    public function encrypt($data, $deterministic);

    /**
     * Must accept data and return decrypted data 
     * @param string $data The data to be decrypted
     * @param bool $deterministic Should the data use a shared key or should the 
     * key be determined individually for each data element.  TRUE - Use the 
     * same key.  FALSE - Use a new key for each.
     */
    public function decrypt($data, $deterministic);
}
