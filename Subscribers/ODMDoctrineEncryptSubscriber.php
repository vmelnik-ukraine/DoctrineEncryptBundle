<?php

namespace VMelnik\DoctrineEncryptBundle\Subscribers;

use VMelnik\DoctrineEncryptBundle\Subscribers\AbstractDoctrineEncryptSubscriber;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;
use \ReflectionClass;

/**
 * Description of ODMDoctrineEncryptSubscriber
 *
 * @author wpigott
 */
class ODMDoctrineEncryptSubscriber extends AbstractDoctrineEncryptSubscriber {

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
     * Listen a prePersist lifecycle event. Checking and encrypt entities
     * which have @Encrypted annotation
     * @param LifecycleEventArgs $args 
     */
    public function prePersist($args) {
        if(!$args instanceof LifecycleEventArgs)
            throw new \InvalidArgumentException('Invalid argument passed.');
        
        $document = $args->getDocument();
        $this->processFields($document);
    }

    /**
     * Listen a preUpdate lifecycle event. Checking and encrypt entities fields
     * which have @Encrypted annotation. Using changesets to avoid preUpdate event
     * restrictions
     * @param LifecycleEventArgs $args 
     */
    public function preUpdate($args) {
        if(!$args instanceof PreUpdateEventArgs)
            throw new \InvalidArgumentException('Invalid argument passed.');
        
        $reflectionClass = new ReflectionClass($args->getDocument());
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $refProperty) {
            if ($this->annReader->getPropertyAnnotation($refProperty, self::ENCRYPTED_ANN_NAME)) {
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
    public function postLoad($args) {
        if(!$args instanceof LifecycleEventArgs)
            throw new \InvalidArgumentException('Invalid argument passed.');
        
        $document = $args->getDocument();
        if (!$this->hasInDecodedRegistry($document, $args->getDocumentManager())) {
            if ($this->processFields($document, false)) {
                $this->addToDecodedRegistry($document, $args->getDocumentManager());
            }
        }
    }

}

?>
