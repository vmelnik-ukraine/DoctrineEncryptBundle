<?php

namespace VMelnik\DoctrineEncryptBundle\Encryptors;

/**
 * Class for AES256 encryption
 * 
 * @author Victor Melnik <melnikvictorl@gmail.com>
 */
class AES256Encryptor implements EncryptorInterface {

    /**
     * Secret key for aes algorythm
     * @var string
     */
    private $secretKey;

    /**
     * Initialization of encryptor
     * @param string $key 
     */
    public function __construct($key) {
        $this->secretKey = $key;
    }

    /**
     * Implementation of EncryptorInterface encrypt method
     * @param string $data
     * @return string
     */
    public function encrypt($data) {
        return trim(base64_encode(mcrypt_encrypt(
                                        MCRYPT_RIJNDAEL_256, $this->secretKey, $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND
                                        ))));
    }

    /**
     * Implementation of EncryptorInterface decrypt method
     * @param string $data
     * @return string 
     */
    function decrypt($data) {
        return trim(mcrypt_decrypt(
                                MCRYPT_RIJNDAEL_256, $this->secretKey, base64_decode($data), MCRYPT_MODE_ECB, mcrypt_create_iv(
                                        mcrypt_get_iv_size(
                                                MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB
                                        ), MCRYPT_RAND
                                )));
    }

}