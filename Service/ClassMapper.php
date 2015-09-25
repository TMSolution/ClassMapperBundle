<?php

namespace Core\ClassMapperBundle\Service;

use OutOfBoundsException;
use UnexpectedValueException;
use InvalidArgumentException;

class ClassMapper
{

    protected $classMapper;
    protected $container;

    public function __construct($container, $classMapper)
    {
        $this->container = $container;
        $this->classMapper = $classMapper;
    }

    public function getEntityClass($name, $locale = '')
    {
        $languages = $this->classMapper['languages'];
        if (array_key_exists($locale, $languages)) {
            if (array_key_exists($name, $languages[$locale]) && !empty($languages[$locale][$name])) {
                return $languages[$locale][$name];
            } else {
                throw new OutOfBoundsException('Entity name "' . $name . '" for locale "' . $locale . '" was not found');
            }
        }
    }

    public function getEntityName($className, $index = 0, $locale = '')
    {

        if (is_int($index) === false) {
            throw new InvalidArgumentException("Invalid type of class index");
        }

        if ($locale == '') {
            $locale = $this->container->get('request')->getLocale();
        }

        $classMap = $this->classMapper['languages'];
        if (isset($classMap[$locale]) == false) {
            throw new OutOfBoundsException('Undefined mapping locale');
        }

        $classMap = $classMap[$locale];

        if (count($classMap) == 0) {
            throw new UnexpectedValueException("No mappings");
        }

        $mapForRequestedClass = array_keys($classMap, $className);
        if (isset($mapForRequestedClass[$index]) == false) {
            throw new OutOfBoundsException("Out of mapping bounds: ('{$index}', '{$className}')");
        }
        return $mapForRequestedClass[$index];
    }
    
     public function getAllEntityNames($locale) {

        $lang = $this->classMapper["languages"];
        try {
            if (isset($lang[$locale]) === false) {
                throw new OutOfBoundsException("Unsupported language");
            }
            return $lang[$locale];    
        } catch (OutOfBoundsException $ex) {
            $defaultLocale = $this->container->getParameter('locale');
            if (isset($lang[$defaultLocale]) === false) {
                throw new OutOfBoundsException("Unsupported language");
            }
            return $lang[$defaultLocale];    
            
        }

    }

}
