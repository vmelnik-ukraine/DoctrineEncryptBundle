<?php

namespace VMelnik\DoctrineEncryptBundle\Encryptors;

/**
 * Encryptable interface. Provides methods for encryptable entities 
 */
interface EncryptableInterface {

    /**
     * Must return an array with encryptable fields names 
     */
    public function getEncryptedFields();
}