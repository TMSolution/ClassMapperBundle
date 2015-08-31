<?php
<?php

/**
 * Copyright (c) 2014, TMSolution
 * All rights reserved.
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Core\ClassMapperBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use ReflectionClass;
use LogicException;
use UnexpectedValueException;

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class GenerateFriendlyNamesCommand extends ContainerAwareCommand {
    
    
    
    protected $correctedEntityNames=[];

    protected function configure() {
        $this->setName('classmapper:generate:friendlynames')
                ->setDescription('Generate  friendly names mapped by entites. You can use it in routes')
                ->addArgument(
                        'bundles', InputArgument::REQUIRED, 'Insert entity class name, use "," to separate bundles names '
        );
    }

    protected function getNamesOfBundles($input) {

        $bundlesString = $input->getArgument('bundles');
        $namesOfBundles = explode(",", $bundlesString);

        foreach ($namesOfBundles as $key => $nameOfBundle) {
            $namesOfBundles[$key] = str_replace('/', '\\', $nameOfBundle);
        }
        return $namesOfBundles;
    }

    protected function readBundleEntities($rootDir, $bundleName) {
        $entityFolderPath = $rootDir . DIRECTORY_SEPARATOR . $bundleName . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR;
        $entities = [];

        $d = dir($entityFolderPath);

        while (false !== ($entry = $d->read())) {

            if ($entry != ".") {

                if (is_file($entityFolderPath . DIRECTORY_SEPARATOR . $entry)) {
                    $entryArr = explode(".", $entry);

                    if ($entryArr[count($entryArr) - 1] == "php") {
                        $entityName = $entryArr[0];

                        $className= $bundleName . '\\' . 'Entity' . '\\' . $entityName;
                        $entities[$className] = [
                            "entityName" => strtolower($entityName),
                            "className" => $className,
                            "bundleName" => mb_substr(strtolower($bundleName),0,-6)
                        ];
                    }
                }
            }
        }
        $d->close();

        return $entities;
        //wez katalog encji, przeczytaj w nim wszystkie nazwy klas
    }

    protected function getSrcDir($rootDir) {
        $rootDirArr = explode(DIRECTORY_SEPARATOR, $rootDir);
        unset($rootDirArr[count($rootDirArr) - 1]);
        $rootDirArr[] = "src";
        return implode(DIRECTORY_SEPARATOR, $rootDirArr);
    }
    
    protected function prepare($entitites)
    {
        $preparedEntities=[];
        
        foreach($entitites as $entity)
        {
            
            if(array_key_exists($entity["entityName"],$preparedEntities))
            {
                $preparedEntities[$entity["bundleName"]."_".$entity["entityName"]]=$entity;
            }
            else
            {
                $preparedEntities[$entity["entityName"]]=$entity;
            }
        }
        return $preparedEntities;
    }

    protected function readEntities($input,$rootDir) {

        $namesOfBundles = $this->getNamesOfBundles($input);
       
        $srcDir = $this->getSrcDir($rootDir);
        $entitites = [];
        
        

        foreach ($namesOfBundles as $nameOfBundle) {

            $bundleEntities = $this->readBundleEntities($srcDir, $nameOfBundle);
            $entitites = array_merge($entitites, $bundleEntities);
        }
        
        return $this->prepare($entitites);
        //$this->findDupliactes($entitites);
       
    }
    
    protected function isFileNameBusy($fileName) {
        if (file_exists($fileName) == true) {
            throw new LogicException("File ".$fileName." exists!");
        }
        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $entities=$this->readEntities($input,$rootDir);
        $fileName=$rootDir.DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR."classmapper.yml";
        $this->isFileNameBusy($fileName);
        $templating = $this->getContainer()->get('templating');
        
        $renderedConfig = $templating->render("CoreClassMapperBundle:Command:classmapper.yml.twig", [
            "entities" => $entities
            ]);
        
        file_put_contents($fileName, $renderedConfig);
        $output->writeln("Classmaper config generated");
    }

}
