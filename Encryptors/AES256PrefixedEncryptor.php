<?php

namespace VMelnik\DoctrineEncryptBundle\Encryptors;

/**
 * Class for AES-256 Prefixed encryption
 * 
 * @author Errin Pace
 */
class AES256PrefixedEncryptor implements EncryptorInterface {

    const CIPHER = MCRYPT_RIJNDAEL_128;
    const MODE = MCRYPT_MODE_CBC;

    /**
     * Prefix to indicate if data is encrypted
     * @var string
     */
    private $prefix = '_ENC_';

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
        $this->secretKey = $this->convertKey($key);
    }

    /**
     * 
     * @param string $prefix
     */
    public function setPrefix($prefix) {
        $this->prefix = $prefix;
    }

    /**
     * Implementation of EncryptorInterface encrypt method
     * @param string $data
     * @return string
     */
    public function encrypt($data) {
        // Generate Initialization Vector
        $iv = $this->generateInitializationVector();

        // Encrypt plaintext data with given parameters
        $encrypted = mcrypt_encrypt(self::CIPHER, $this->secretKey, $data, self::MODE, $iv);

        // Encode data with MIME base64
        $base64_encoded = base64_encode($encrypted);

        // Strip NULL-bytes from the end of the string
        $rtrimmed = rtrim($base64_encoded, "\0");

        return $this->prefix . $rtrimmed;
    }

    /**
     * Implementation of EncryptorInterface decrypt method
     * @param string $data
     * @return string 
     */
    public function decrypt($data) {
        // Return data if not annotated as encrypted
        if (strncmp($this->prefix, $data, strlen($this->prefix)) !== 0)
            return $data;

        // Strip annotation from data
        $annotation_removed = str_replace($this->prefix, '', $data, 1);

        // Generate Initialization Vector
        $iv = $this->generateInitializationVector();

        // Decode data encoded with MIME base64
        $base64_decoded = base64_decode($annotation_removed);

        // Decrypt crypttext with given parameters
        $decrypted = mcrypt_decrypt(self::CIPHER, $this->secretKey, $base64_decoded, self::MODE, $iv);

        // Strip NULL-bytes from the end of the string and return
        return rtrim($decrypted, "\0");
    }

    /**
     * 
     * @param string $secretKey
     * @return string
     */
    private function convertKey($secretKey) {
        return pack('H*', hash('sha256', $secretKey));
    }

    private function generateInitializationVector() {
        // Get the size of the Initialization Vector (IV) belonging to a specific cipher/mode combination
        $iv_size = mcrypt_get_iv_size(self::CIPHER, self::MODE);

        // Return an initialization vector (IV) from a random source
        return mcrypt_create_iv($iv_size, MCRYPT_DEV_URANDOM);
    }

}