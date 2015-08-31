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

                dump($entry);

                if (is_file($entityFolderPath . DIRECTORY_SEPARATOR . $entry)) {
                    $entryArr = explode(".", $entry);

                    if ($entryArr[count($entryArr) - 1] == "php") {
                        $entityName = $entryArr[0];

                        $entities[] = [
                            "entityName" => strtolower($entityName),
                            "className" => $bundleName . '\\' . 'Entity' . '\\' . $entityName
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

    /*@todo*/
    protected function changeDuplicatedNames($entities) {

        $counter = 0;
        foreach ($entities as $key=> $entity) {
            if ($counter > 0) {
                $entities[$key]["entityName"] = $entity["entityName"] . '_' . $counter;
            }
        }
        return $entities;
    }

    protected function findDupliactes($bundlesEntities) {

        foreach ($bundlesEntities as $bundleEntity) {
            $entityName = $bundleEntity["entityName"];
            $entities = array_filter($bundlesEntities, function($item,$key) use ($entityName,$bundlesEntities) {

                if ($item["entityName"] == $entityName) {
                    return $bundlesEntities[$item["entityName"]];
                }
            }, ARRAY_FILTER_USE_BOTH
            );
     
            if (count($entities) > 1) {
                $this->changeDuplicatedNames($entities);
            }
            
        }
    }

    protected function readEntities($input) {

        $namesOfBundles = $this->getNamesOfBundles($input);
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $srcDir = $this->getSrcDir($rootDir);
        $entitites = [];

        foreach ($namesOfBundles as $nameOfBundle) {

            $bundleEntities = $this->readBundleEntities($srcDir, $nameOfBundle);
            $entitites = array_merge($entitites, $bundleEntities);
        }
        
        $this->findDupliactes($entitites);
       
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->readEntities($input);

        // file_put_contents($fileName, $renderedConfig);
        $output->writeln("Classmaper config generated");
    }

}
