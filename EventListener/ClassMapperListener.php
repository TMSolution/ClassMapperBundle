<?php

namespace Core\PrototypeBundle\EventListener;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ClassMapperListener
{

    protected $container;

    public function __construct($container)
    {

        $this->container = $container;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->attributes->get('entityName')) {
            $objectName = $this->container->get("prototype.classmapperservice")->getEntityClass($request->attributes->get('entityName'), $request->getLocale());
            $request->attributes->set('objectName', $objectName);
        } else {
            
         
        }
        
        
    }

}
