<?php

namespace VMelnik\DoctrineEncryptBundle\Encryptors;

use VMelnik\DoctrineEncryptBundle\Encryptors\EncryptorInterface;

/**
 *
 * @author wpigott
 */
interface EncryptorServiceInterface extends EncryptorInterface {
    /**
     * Must accept secret key for encryption 
     */
    public function __construct();
    
    public function setSecretKey($secretKey);
}

?>
