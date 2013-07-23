<?php

namespace Pum\Bundle\WoodworkBundle\Controller;

use Pum\Core\Exception\BeamNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Pum\Bundle\WoodworkBundle\Form\Type\ObjectDefinitionType;
use Symfony\Component\HttpFoundation\Request;

class BeamController extends Controller
{
    public function listAction()
    {
        return $this->render('PumWoodworkBundle:BeamDefinition:list.html.twig', array(
            'beams' => $this->get('pum')->getAllBeams()
        ));
    }

}