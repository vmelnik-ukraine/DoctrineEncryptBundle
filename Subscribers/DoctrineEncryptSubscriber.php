<?php

namespace VMelnik\DoctrineEncryptBundle\Subscribers;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\Annotations\Reader;
use \ReflectionClass;

/**
 * Doctrine event subscriber which encrypt/decrypt entities
 */
class DoctrineEncryptSubscriber implements EventSubscriber {
    
    /**
     * Encryptor interface namespace 
     */
    const ENCRYPTOR_INTERFACE_NS = 'VMelnik\DoctrineEncryptBundle\Encryptors\EncryptorInterface';
    
    /**
     * Encrypted annotation full name
     */
    const ENCRYPTED_ANN_NAME = 'VMelnik\DoctrineEncryptBundle\Configuration\Encrypted';

    /**
     * Encryptor
     * @var EncryptorInterface 
     */
    private $encryptor;

    /**
     * Annotation reader
     * @var Doctrine\Common\Annotations\Reader
     */
    private $annReader;

    /**
     * Initialization of subscriber
     * @param string $encryptorClass
     * @param string $secretKey
     */
    public function __construct(Reader $annReader, $encryptorClass, $secretKey) {
        $this->annReader = $annReader;
        $this->encryptor = $this->encryptorFactory($encryptorClass, $secretKey);
    }

    /**
     * Listen a prePersist lifecycle event. Checking and encrypt entities
     * which have @Encrypted annotation
     * @param LifecycleEventArgs $args 
     */
    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        $this->processFields($entity);
    }

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations
     * @param LifecycleEventArgs $args 
     */
    public function postLoad(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        $this->processFields($entity, false);
    }

    /**
     * Realization of EventSubscriber interface method.
     * @return Array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents() {
        return array(
            Events::prePersist,
            Events::postLoad,
        );
    }

    /**
     * Process (encrypt/decrypt) entities fields
     * @param Obj $entity Some doctrine entity
     * @param Boolean $isEncryptOperation If true - encrypt, false - decrypt entity 
     */
    private function processFields($entity, $isEncryptOperation = true) {
        $encryptorMethod = $isEncryptOperation ? 'encrypt' : 'decrypt';
        $reflectionClass = new ReflectionClass($entity);
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $refProperty) {
            if ($this->annReader->getPropertyAnnotation($refProperty, self::ENCRYPTED_ANN_NAME)) {
                $propName = $refProperty->getName();
                if ($refProperty->isPublic()) {
                    $entity->$propName = $this->encryptor->$encryptorMethod($refProperty->getValue());
                } else {
                    $methodName = str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $propName)));
                    if ($reflectionClass->hasMethod($getter = 'get' . $methodName) && $reflectionClass->hasMethod($setter = 'set' . $methodName)) {
                        $currentPropValue = $this->encryptor->$encryptorMethod($entity->$getter());
                        $entity->$setter($currentPropValue);
                    } else {
                        throw new \RuntimeException(sprintf("Property %s isn't public and doesn't has getter/setter"));
                    }
                }
            }
        }
    }

    /**
     * Encryptor factory. Checks and create needed encryptor
     * @param string $classFullName Encryptor namespace and name
     * @param string $secretKey Secret key for encryptor
     * @return EncryptorInterface
     * @throws \RuntimeException 
     */
    private function encryptorFactory($classFullName, $secretKey) {
        $refClass = new \ReflectionClass($classFullName);
        if ($refClass->implementsInterface(self::ENCRYPTOR_INTERFACE_NS)) {
            return new $classFullName($secretKey);
        } else {
            throw new \RuntimeException('Encryptor must implements interface EncryptorInterface');
        }
    }

}