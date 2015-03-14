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
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'The entity to encrypt in the DB - MyComp\\MyBundle\\MyEntity')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'Entity Manager you are using to manage this entity','default')
            ->addOption('id_column', null, InputOption::VALUE_OPTIONAL, 'If your entity is not indexed by a column called "id"','id');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager($input->getOption('em'));
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $metaInfo = $em->getClassMetadata($input->getOption('entity'));
        $propertiesToUpdate = array();
        foreach ($metaInfo->getReflectionProperties() AS $prop) {
            if(preg_match('/@Encrypted/', $prop->getDocComment()))
            {
                $propertiesToUpdate[] = $metaInfo->getColumnName($prop->getName());
            }
        }

        $classname=$input->getOption('entity');
        
        $repository = $em->getRepository($classname);
        
        $count=0;
    
        $query = $em->createQuery('SELECT r.'.$input->getOption('id_column').' FROM '.$classname.' r');

        $res = $query->getArrayResult();

        $encryptor = $this->getContainer()->get('vmelnik_doctrine_encrypt.subscriber');//new AES256Encryptor();

        $sql = "SELECT ";
        $fields = array();
        
        foreach ($propertiesToUpdate as $prop) {
            $fields[] = $prop;
        }
        
        $sql .= implode(",", $fields);
        $sql .= " FROM ".$em->getClassMetadata($classname)->getTableName()." WHERE ".$input->getOption('id_column')."=";

        $encryptor = new AES256Encryptor($this->getContainer()->getParameter('vmelnik_doctrine_encrypt.secret_key'));

        $sql_update = "UPDATE ".$em->getClassMetadata($classname)->getTableName()." SET ";
        $i=0;
        foreach($res as $id)
        {
            $output->writeln("<comment>Processing entity with ID: ".$id[$input->getOption('id_column')]."</comment>");
            $currentQuery = $sql."'".$id[$input->getOption('id_column')]."'";
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
            $currentUpdate .= " WHERE ".$input->getOption('id_column')."='".$id[$input->getOption('id_column')]."'";
            $stmt = $em->getConnection()->prepare($currentUpdate);
            $stmt->execute();
            $i++;
            if($i % 1000 == 0){$output->writeln("<comment>Processed ".$i." entities</comment>");}
        }
    }
}