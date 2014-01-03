<?php

namespace VMelnik\DoctrineEncryptBundle\Configuration;

use Doctrine\Common\Annotations\Annotation;

/**
 * The Encrypted class handles the @Encrypted annotation.
 *
 * @author Victor Melnik <melnikvictorl@gmail.com>
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Encrypted {

    private $deterministic = FALSE;
    private $decrypt = FALSE;

    public function __construct(array $options) {
        $this->deterministic = (array_key_exists('deterministic', $options) && $options['deterministic'] === TRUE) ? TRUE : FALSE;
        $this->decrypt = (array_key_exists('decrypt', $options) && $options['decrypt'] === TRUE) ? TRUE : FALSE;
    }

    public function getDeterministic() {
        return $this->deterministic;
    }

    public function getDecrypt() {
        return $this->decrypt;
    }

}
