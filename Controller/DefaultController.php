<?php

namespace Core\ClassMapperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('CoreClassMapperBundle:Default:index.html.twig', array('name' => $name));
    }
}
