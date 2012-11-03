<?php

namespace VMelnik\DoctrineEncryptBundle\Subscribers;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use \ReflectionClass;

/**
 * Doctrine event subscriber which encrypt/decrypt entities
 */
class DoctrineEncryptSubscriber implements EventSubscriber {

    /**
     * Encryptor
     * @var EncryptorInterface 
     */
    private $encryptor;

    /**
     * Encryptor interface namespace 
     */

    const ENCRYPTOR_INTERFACE_NS = 'VMelnik\DoctrineEncryptBundle\Encryptors\EncryptorInterface';

    /**
     * Encryptable interface namespace 
     */
    const ENCRYPTABLE_INTERFACE_NS = 'VMelnik\DoctrineEncryptBundle\Encryptors\EncryptableInterface';

    /**
     * Initialization of subscriber
     * @param string $encryptorClass
     * @param string $secretKey
     */
    public function __construct($encryptorClass, $secretKey) {
        $this->encryptor = $this->encryptorFactory($encryptorClass, $secretKey);
    }

    /**
     * Listen a prePersist lifecycle event. Checking and encrypt entities
     * which implements EncryptableInterface interface
     * @param LifecycleEventArgs $args 
     */
    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        $this->processFields($entity);
    }

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which implements EncryptableInterface interface
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
     * Process (encrypt/decrypt) entities fields which implements EncryptableInterface interface
     * @param Obj $entity Some doctrine entity
     * @param Boolean $isEncryptOperation If true - encrypt, false - decrypt entity 
     */
    private function processFields($entity, $isEncryptOperation = true) {
        $encryptorMethod = $isEncryptOperation ? 'encrypt' : 'decrypt';
        $reflectionClass = new ReflectionClass($entity);
        if ($reflectionClass->implementsInterface(self::ENCRYPTABLE_INTERFACE_NS)) {
            $mustEncryptProps = $entity->getEncryptedFields();
            foreach ($mustEncryptProps as $propName) {
                $property = $reflectionClass->getProperty($propName);
                if ($property->isPublic()) {
                    $entity->$propName = $this->encryptor->$encryptorMethod($property->getValue());
                } else {
                    $methodName = str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $propName)));
                    if ($reflectionClass->hasMethod('get' . $methodName) && $reflectionClass->hasMethod('set' . $methodName)) {
                        $getter = 'get' . $methodName;
                        $setter = 'set' . $methodName;
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