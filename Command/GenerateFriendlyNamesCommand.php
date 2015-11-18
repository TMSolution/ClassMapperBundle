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
use Core\PrototypeBundle\Component\Yaml\Parser;
use Core\PrototypeBundle\Component\Yaml\Dumper;

/**
 * GridConfigCommand generates widget class and his template.
 * @author Mariusz Piela <mariuszpiela@gmail.com>
 */
class GenerateFriendlyNamesCommand extends ContainerAwareCommand
{

    protected $correctedEntityNames = [];
    protected $yamlArr = [];
    protected $languages = [];
    protected $output = null;

    protected function configure()
    {
        $this->setName('classmapper:generate:friendlynames')
                ->setDescription('Generate  friendly names mapped by entites. You can use it in routes')
                ->addArgument('bundles', InputArgument::REQUIRED, 'Insert entity class name, use "," to separate bundles names ')
                ->addArgument('languages', InputArgument::REQUIRED, 'Insert languages, separated by commas');
    }

    protected function getNamesOfBundles($input)
    {

        $bundlesString = $input->getArgument('bundles');
        $namesOfBundles = explode(",", $bundlesString);

        foreach ($namesOfBundles as $key => $nameOfBundle) {
            $namesOfBundles[$key] = str_replace('/', '\\', $nameOfBundle);
        }
        return $namesOfBundles;
    }

    protected function getLanguages($input)
    {

        $this->languages = explode(',', $input->getArgument('languages'));
    }

    protected function readBundleEntities($rootDir, $bundleName)
    {
        $entityFolderPath = $rootDir . DIRECTORY_SEPARATOR . $bundleName . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR;
        $entities = [];

        $d = dir($entityFolderPath);

        while (false !== ($entry = $d->read())) {

            if ($entry != ".") {

                if (is_file($entityFolderPath . DIRECTORY_SEPARATOR . $entry)) {
                    $entryArr = explode(".", $entry);

                    if ($entryArr[count($entryArr) - 1] == "php") {
                        $entityName = $entryArr[0];

                        $className = $bundleName . '\\' . 'Entity' . '\\' . $entityName;
                        $entities[$className] = [
                            "entityName" => strtolower($entityName),
                            "className" => $className,
                            "bundleName" => mb_substr(strtolower($bundleName), 0, -6)
                        ];
                    }
                }
            }
        }
        $d->close();

        return $entities;
        //wez katalog encji, przeczytaj w nim wszystkie nazwy klas
    }

    protected function getSrcDir($rootDir)
    {
        $rootDirArr = explode(DIRECTORY_SEPARATOR, $rootDir);
        unset($rootDirArr[count($rootDirArr) - 1]);
        $rootDirArr[] = "src";
        return implode(DIRECTORY_SEPARATOR, $rootDirArr);
    }

    protected function prepare($entitites)
    {

        foreach ($this->languages as $language) {

            foreach ($entitites as $entity) {

                if (!array_key_exists($entity['entityName'], $this->yamlArr['core_class_mapper']['languages'][$language])) {
                    $this->yamlArr['core_class_mapper']['languages'][$language][$entity['entityName']] = $entity['className'];
                }
            }
        }

        return $this->yamlArr;
    }

    protected function readEntities($input, $rootDir)
    {

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

    protected function isFileNameBusy($fileName)
    {
        if (file_exists($fileName) == true) {
            throw new LogicException("File " . $fileName . " exists!");
        }
        return false;
    }

    protected function readYml($configFullPath)
    {
        try {
            if (file_exists($configFullPath)) {
                $yaml = new Parser();
                $this->yamlArr = $yaml->parse(file_get_contents($configFullPath));
                if ($this->yamlArr === NULL) {

                    $this->yamlArr = ['core_class_mapper' => ['languages' => []]];
                    foreach ($this->languages as $language) {
                        $this->yamlArr['core_class_mapper']['languages'][] = [$language];
                    }
                }
            } else {
                $this->yamlArr = ['core_class_mapper' => ['languages' => []]];
                foreach ($this->languages as $language) {
                    $this->yamlArr['core_class_mapper']['languages'][] = [$language];
                }
            }

            return $this->yamlArr;
        } catch (\Exception $e) {
            throw new \Exception('Error reading yml file.');
        }
    }

    protected function writeYml($fileName)
    {

        $yaml = new Dumper();
        $yamlData = $yaml->dump($this->yamlArr, 4, 0, false, true);

        file_put_contents($fileName, $yamlData);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $filePath = $rootDir . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "classmapper.yml";

        $this->getLanguages($input);
        $this->readYml($filePath);
        $this->readEntities($input, $rootDir);
        $this->writeYml($filePath);


        $output->writeln("Classmaper config generated");
    }

}
