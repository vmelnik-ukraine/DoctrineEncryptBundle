<?php

namespace VMelnik\DoctrineEncryptBundle\Subscribers;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Common\Annotations\Reader;
use \Doctrine\ORM\EntityManager;
use \ReflectionClass;
use VMelnik\DoctrineEncryptBundle\Encryptors\EncryptorInterface;

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
     * Registry to avoid multi decode operations for one entity
     * @var array
     */
    private $decodedRegistry = array();

    /**
     * Initialization of subscriber
     * @param string $encryptorClass  The encryptor class.  This can be empty if 
     * a service is being provided.
     * @param string $secretKey The secret key. 
     * @param EncryptorInterface|NULL $service (Optional)  An EncryptorInterface.
     * This allows for the use of dependency injection for the encrypters.
     */
    public function __construct(Reader $annReader, $encryptorClass, $secretKey, EncryptorInterface $service = NULL) {
        $this->annReader = $annReader;
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
    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();

        $metadata = $args->getEntityManager()->getMetadataFactory()->getMetadataFor(get_class($entity));
        $this->processFields($entity,true,$metadata);
    }

    /**
     * Listen a preUpdate lifecycle event. Checking and encrypt entities fields
     * which have @Encrypted annotation. Using changesets to avoid preUpdate event
     * restrictions
     * @param PreUpdateEventArgs $args 
     */
    public function preUpdate(PreUpdateEventArgs $args) {
        $entity = $args->getEntity();
        $reflectionClass = new ReflectionClass($entity);
        $metadata = $args->getEntityManager()->getMetadataFactory()->getMetadataFor(get_class($entity));
        if($metadata !== null){
            $fieldMappings = $metadata->fieldMappings;
        }
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $refProperty) {
            //check annotations for encrypted flag
            $encrypt = $this->annReader->getPropertyAnnotation($refProperty, self::ENCRYPTED_ANN_NAME);
            $refPropertyName = $refProperty->getName();
            //check metadata for encrypted type
            if(array_key_exists($refPropertyName,$fieldMappings) && array_key_exists('type',$fieldMappings[$refPropertyName])){
                if($fieldMappings[$refPropertyName]['type'] == 'encrypted'){
                    $encrypt = true;
                }
            }

            if ($encrypt) {
                $propName = $refProperty->getName();
                $args->setNewValue($propName, $this->encryptor->encrypt($args->getNewValue($propName)));
            }
        }
    }

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations
     * @param LifecycleEventArgs $args 
     */
    public function postLoad(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        $metadata = $args->getEntityManager()->getMetadataFactory()->getMetadataFor(get_class($entity));
        if (!$this->hasInDecodedRegistry($entity, $args->getEntityManager())) {
            if ($this->processFields($entity, false,$metadata)) {
                $this->addToDecodedRegistry($entity, $args->getEntityManager());
            }
        }
    }

    /**
     * Realization of EventSubscriber interface method.
     * @return Array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents() {
        return array(
            Events::prePersist,
            Events::preUpdate,
            Events::postLoad,
        );
    }

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
     * @param Obj $entity Some doctrine entity
     * @param Boolean $isEncryptOperation If true - encrypt, false - decrypt entity 
     */
    private function processFields($entity, $isEncryptOperation = true,$metadata=null) {
        $encryptorMethod = $isEncryptOperation ? 'encrypt' : 'decrypt';
        $fieldMappings =[];
        if($metadata !== null){
            $fieldMappings = $metadata->fieldMappings;
        }
        $reflectionClass = new ReflectionClass($entity);

        $properties = $reflectionClass->getProperties();
        $withAnnotation = false;
        foreach ($properties as $refProperty) {
            //check annotations for encrypted flag
            $encrypt = $this->annReader->getPropertyAnnotation($refProperty, self::ENCRYPTED_ANN_NAME);
            $refPropertyName = $refProperty->getName();
            //check metadata for encrypted type
            if(array_key_exists($refPropertyName,$fieldMappings)){
                if(array_key_exists('type',$fieldMappings[$refPropertyName])){
                    if($fieldMappings[$refPropertyName]['type'] == 'encrypted'){
                        $encrypt = true;
                    }
                }

            }

            if ($encrypt) {
                $withAnnotation = true;
                // we have annotation and if it decrypt operation, we must avoid duble decryption
                $propName = $refProperty->getName();
                if ($refProperty->isPublic()) {
                    $entity->$propName = $this->encryptor->$encryptorMethod($refProperty->getValue());
                } else {
                    $methodName = self::capitalize($propName);
                    if ($reflectionClass->hasMethod($getter = 'get' . $methodName) && $reflectionClass->hasMethod($setter = 'set' . $methodName)) {
                        $currentPropValue = $this->encryptor->$encryptorMethod($entity->$getter());
                        $entity->$setter($currentPropValue);
                    } else {
                        throw new \RuntimeException(sprintf("Property %s isn't public and doesn't has getter/setter"));
                    }
                }
            }
        }

        return $withAnnotation;
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
     * @param \Doctrine\ORM\EntityManager $em
     * @return boolean
     */
    private function hasInDecodedRegistry($entity, EntityManager $em) {
        $className = get_class($entity);
        $metadata = $em->getClassMetadata($className);
        $getter = 'get' . self::capitalize($metadata->getIdentifier());

        return isset($this->decodedRegistry[$className][$entity->$getter()]);
    }

    /**
     * Adds entity to decoded registry
     * @param object $entity Some doctrine entity
     * @param \Doctrine\ORM\EntityManager $em
     */
    private function addToDecodedRegistry($entity, EntityManager $em) {
        $className = get_class($entity);
        $metadata = $em->getClassMetadata($className);
        $getter = 'get' . self::capitalize($metadata->getIdentifier());
        $this->decodedRegistry[$className][$entity->$getter()] = true;
    }

}
