<?php

namespace VMelnik\DoctrineEncryptBundle\Subscribers;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\ObjectManager;
use \ReflectionClass;
use VMelnik\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use VMelnik\DoctrineEncryptBundle\Configuration\Encrypted;
use \ReflectionProperty;
use \Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Doctrine event subscriber which encrypt/decrypt entities
 */
abstract class AbstractDoctrineEncryptSubscriber implements EventSubscriber {

    /**
     * Encryptor interface namespace 
     */
    const ENCRYPTOR_INTERFACE_NS = 'VMelnik\DoctrineEncryptBundle\Encryptors\EncryptorInterface';

    /**
     * Encrypted annotation full name
     */
    const ENCRYPTED_ANN_NAME = 'VMelnik\DoctrineEncryptBundle\Configuration\Encrypted';
    const CUSTOM_VALUE_HANDLER_EVENT = 'custom_encrypt_handler';

    /**
     * Encryptor
     * @var EncryptorInterface 
     */
    protected $encryptor;

    /**
     * Annotation reader
     * @var Doctrine\Common\Annotations\Reader
     */
    protected $annReader;

    /**
     *
     * @var ContainerInterface 
     */
    private $container;

    /**
     * Registr to avoid multi decode operations for one entity
     * @var array
     */
    protected $decodedRegistry = array();

    /**
     * Initialization of subscriber
     * @param string $encryptorClass  The encryptor class.  This can be empty if 
     * a service is being provided.
     * @param string $secretKey The secret key. 
     * @param EncryptorServiceInterface|NULL $service (Optional)  An EncryptorServiceInterface.  
     * This allows for the use of dependency injection for the encrypters.
     */
    public function __construct(Reader $annReader, ContainerInterface $container, $encryptorClass, $secretKey, EncryptorInterface $service = NULL) {
        $this->annReader = $annReader;
        $this->container = $container;
        if ($service instanceof EncryptorInterface) {
            $this->encryptor = $service;
        } else {
            $this->encryptor = $this->encryptorFactory($encryptorClass, $secretKey);
        }
    }

    /**
     * Listen a prePersist lifecycle event. Checking and encrypt entities
     * which have @Encrypted annotation
     * @param LifecycleEventArgs $args 
     */
    abstract public function prePersist($args);

    /**
     * Listen a preUpdate lifecycle event. Checking and encrypt entities fields
     * which have @Encrypted annotation. Using changesets to avoid preUpdate event
     * restrictions
     * @param LifecycleEventArgs $args 
     */
    abstract public function preUpdate($args);

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations
     * @param LifecycleEventArgs $args 
     */
    abstract public function postLoad($args);

    /**
     * Realization of EventSubscriber interface method.
     * @return Array Return all events which this subscriber is listening
     */
    abstract public function getSubscribedEvents();

    /**
     * Capitalize string
     * @param string $word
     * @return string
     */
    public static function capitalize($word) {
        if (is_array($word)) {
            $word = $word[0];
        }

        return str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $word)));
    }

    /**
     * Process (encrypt/decrypt) entities fields
     * @param Obj $object Some doctrine entity
     * @param Boolean $isEncryptOperation If true - encrypt, false - decrypt entity 
     */
    protected function processFields($object, $isEncryptOperation = true) {
        $encryptorMethod = $isEncryptOperation ? 'encrypt' : 'decrypt';
        $properties = $this->getReflectionProperties($object);
        $withAnnotation = false;
        foreach ($properties as $refProperty) {
            if ($this->processSingleField($object, $refProperty, $encryptorMethod)) {
                $withAnnotation = TRUE;
            }
        }
        return $withAnnotation;
    }

    private function processSingleField($object, ReflectionProperty $refProperty, $encryptorMethod) {
        $annotation = $this->getAnnotation($refProperty);
        if (NULL === $annotation) {
            return FALSE;
        }

        if (!(($annotation->getDecrypt()) && ('encrypt' === $encryptorMethod))) {
            $refProperty->setAccessible(TRUE);
            $refProperty->setValue($object, $this->determineNewValue($annotation, $refProperty->getValue($object), $object, $encryptorMethod));
        }
        return TRUE;
    }

    private function determineNewValue(Encrypted $annotation, $currentValue, $object, $encryptorMethod) {
        //Check if there is a custom handler for the field.
        $customMethod = $annotation->getHandlerMethod();
        $customService = $annotation->getHandlerService();
        if ((!NULL === $customService) && (!NULL === $customMethod)) {
            $service = $this->getService($customService);
            // Check that the method is valid.
            if (!is_callable(array($service, $customMethod))) {
                throw new Exception('Method "' . $customMethod . '" is not a callable method.');
            }
            return $service->$customMethod($this->encryptor, $currentValue, $encryptorMethod);
        }
        return $this->handleValue($encryptorMethod, $currentValue, $annotation->getDeterministic());
    }

    private function getService($customService) {
        if ($this->container->has($customService)) {
            return $this->container->get($customService);
        }
        throw new Exception('Unable to locate custom service "' . $customService . '".');
    }

    /**
     * This method can be overridden to handle a specific data type differently.  
     * IE.  Override this to handle arrays specifically with MongoDB.
     * @param type $encryptorMethod
     * @param type $value
     * @param type $deterministic
     */
    protected function handleValue($encryptorMethod, $value, $deterministic) {
        return $this->encryptor->$encryptorMethod($value, $deterministic);
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

    /**
     * Check if we have entity in decoded registry
     * @param Object $entity Some doctrine entity
     * @param Doctrine\Common\Persistence\ObjectManager $em
     * @return boolean
     */
    protected function hasInDecodedRegistry($entity, ObjectManager $om) {
        $className = get_class($entity);
        $metadata = $om->getClassMetadata($className);
        $suffix = self::capitalize($metadata->getIdentifier());
        if ($suffix == '')
            return FALSE;
        $getter = 'get' . $suffix;

        return isset($this->decodedRegistry[$className][$entity->$getter()]);
    }

    /**
     * Adds entity to decoded registry
     * @param object $entity Some doctrine entity
     * @param Doctrine\Common\Persistence\ObjectManager $em
     */
    protected function addToDecodedRegistry($entity, ObjectManager $om) {
        $className = get_class($entity);
        $metadata = $om->getClassMetadata($className);
        $suffix = self::capitalize($metadata->getIdentifier());
        if ($suffix == '')
            return FALSE;
        $getter = 'get' . $suffix;
        $this->decodedRegistry[$className][$entity->$getter()] = true;
    }

    /**
     * 
     * @param ReflectionProperty $reflectionProperty
     * @return Encrypted|NULL
     */
    protected function getAnnotation(ReflectionProperty $reflectionProperty) {
        return $this->annReader->getPropertyAnnotation($reflectionProperty, self::ENCRYPTED_ANN_NAME);
    }

    /**
     * 
     * @param mixed $object
     * @return ReflectionProperty[]
     */
    protected function getReflectionProperties($object) {
        $reflectionClass = new ReflectionClass($object);
        return $reflectionClass->getProperties();
    }

}
