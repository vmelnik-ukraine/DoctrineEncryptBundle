<?php

namespace VMelnik\DoctrineEncryptBundle\Encryptors;

/**
 * Class for AES256 with CBC Mode encryption
 * 
 * @author Dody Suria Wijaya <dodysw@gmail.com>
 */
class AES256CBCEncryptor implements EncryptorInterface {

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var int 
     */
	private $ivSize;

    /**
     * {@inheritdoc}
     */
    public function __construct($key) {
        $this->secretKey = md5($key);
		$this->ivSize = mcrypt_get_iv_size(
			MCRYPT_RIJNDAEL_256, 
			MCRYPT_MODE_CBC
		);
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt($data) {
		$iv = mcrypt_create_iv( $this->ivSize, MCRYPT_RAND);
		return trim(base64_encode(
			$iv . 
			mcrypt_encrypt(
				MCRYPT_RIJNDAEL_256,
				$this->secretKey,
				$data,
				MCRYPT_MODE_CBC,
				$iv
        )));
    }

    /**
     * {@inheritdoc}
     */
    function decrypt($data) {
		$chipertext = base64_decode($data);
		if (strlen($chipertext) < $this->ivSize) {
			return null;
		}
        return trim(mcrypt_decrypt(
            MCRYPT_RIJNDAEL_256,
            $this->secretKey,
			substr($chipertext, $this->ivSize),
            MCRYPT_MODE_CBC,
			substr($chipertext, 0, $this->ivSize)
        ));
    }
}
