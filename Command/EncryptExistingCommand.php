<?php

namespace VMelnik\DoctrineEncryptBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

use VMelnik\DoctrineEncryptBundle\DependencyInjection\VMelnikDoctrineEncryptExtension;
use VMelnik\DoctrineEncryptBundle\DependencyInjection\Compiler\RegisterServiceCompilerPass;
use VMelnik\DoctrineEncryptBundle\Encryptors\AES256Encryptor;

class EncryptExistingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('encrypt:existing')
            ->setDescription('Encrypt the existing data in the database - LOCK THE TABLE BEFORE THIS - Do not do this if you have already encrypted data - ONLY FOR AES256 ENCRYPTION')
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'The entity to encrypt in the DB - MyComp\\MyBundle\\MyEntity');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reflClass = new \ReflectionClass($input->getOption('entity'));
        $propertiesToUpdate = array();
        foreach ($reflClass->getProperties() AS $prop) {
            if(preg_match('/@Encrypted/', $prop->getDocComment()))
            {
                $propertiesToUpdate[] = $prop;
            }
        }

        $classname=$input->getOption('entity');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository($classname);
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $count=0;
    
        $query = $em->createQuery('SELECT r.id FROM '.$classname.' r');

        $res = $query->getArrayResult();

        $encryptor = $this->getContainer()->get('vmelnik_doctrine_encrypt.subscriber');//new AES256Encryptor();

        $manualEncryptor = new AES256Encryptor($this->getContainer()->getParameter('vmelnik_doctrine_encrypt.secret_key'));

        $sql = "SELECT ";
        $fields = array();
        
        foreach ($propertiesToUpdate as $prop) {
            $fields[] = $prop->name;
        }
        
        $sql .= implode(",", $fields);
        $sql .= " FROM ".$em->getClassMetadata($classname)->getTableName()." WHERE id=";

        $encryptor = new AES256Encryptor($this->getContainer()->getParameter('vmelnik_doctrine_encrypt.secret_key'));

        $sql_update = "UPDATE ".$em->getClassMetadata($classname)->getTableName()." SET ";
        $i=0;
        foreach($res as $id)
        {
            $output->writeln("<comment>Processing entity with ID: ".$id['id']."</comment>");
            $currentQuery = $sql.$id['id'];
            $currentUpdate = $sql_update;
            $stmt = $em->getConnection()->prepare($currentQuery);
            $stmt->execute();
            $rawSqlArray = $stmt->fetchAll();
            $newValues = array();

            foreach ($rawSqlArray[0] as $key => $value) 
            {
                $newValues[] = $key."='".$encryptor->encrypt($value)."'";
            }

            $currentUpdate .= implode(",", $newValues);
            $currentUpdate .= " WHERE id=".$id['id'];
            $stmt = $em->getConnection()->prepare($currentUpdate);
            $stmt->execute();
            $i++;
            if($i % 1000 == 0){$output->writeln("<comment>Processed ".$i." entities</comment>");}
        }
    }
}