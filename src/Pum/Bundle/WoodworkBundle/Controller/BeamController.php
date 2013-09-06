<?php

namespace Pum\Bundle\WoodworkBundle\Controller;

use Pum\Core\Definition\Beam;
use Pum\Core\Exception\BeamNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Form\FormError;


class BeamController extends Controller
{
    /**
     * @Route(path="/beams", name="ww_beam_list")
     */
    public function listAction()
    {
        $this->assertGranted('ROLE_WW_BEAMS');

        return $this->render('PumWoodworkBundle:Beam:list.html.twig', array(
            'beams' => $this->get('pum')->getAllBeams()
        ));
    }

    /**
     * @Route(path="/beams/create", name="ww_beam_create")
     */
    public function createAction(Request $request)
    {
        $this->assertGranted('ROLE_WW_BEAMS');

        $manager = $this->get('pum');

        $form = $this->createForm('ww_beam');
        if ($request->isMethod('POST') && $form->bind($request)->isValid()) {
            $manager->saveBeam($form->getData());
            $this->addSuccess('Beam successfully created');

            return $this->redirect($this->generateUrl('ww_beam_edit', array('beamName' => $form->getData()->getName())));
        }

        return $this->render('PumWoodworkBundle:Beam:create.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route(path="/beams/{beamName}/edit", name="ww_beam_edit")
     * @ParamConverter("beam", class="Beam")
     */
    public function editAction(Request $request, Beam $beam)
    {
        $this->assertGranted('ROLE_WW_BEAMS');

        $manager  = $this->get('pum');
        $beamView = clone $beam;

        $form = $this->createForm('ww_beam', $beam);
        if ($request->isMethod('POST') && $form->bind($request)->isValid()) {
            $manager->saveBeam($form->getData());
            $this->addSuccess('Beam successfully updated');

            return $this->redirect($this->generateUrl('ww_beam_edit', array('beamName' => $form->getData()->getName())));
        }

        return $this->render('PumWoodworkBundle:Beam:edit.html.twig', array(
            'beam' => $beamView,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route(path="/beams/{beamName}/clone", name="ww_beam_clone")
     * @ParamConverter("beam", class="Beam")
     */
    public function cloneAction(Request $request, Beam $beam)
    {
        $this->assertGranted('ROLE_WW_BEAMS');

        $manager  = $this->get('pum');
        $newBeam = $beam->duplicate(); // new instance, loose binding to any existing entity.

        $form = $this->createForm('ww_beam', $newBeam);
        if ($request->isMethod('POST') && $form->bind($request)->isValid()) {
            $manager->saveBeam($newBeam);
            $this->addSuccess('Beam successfully cloned');

            return $this->redirect($this->generateUrl('ww_beam_list'));
        }

        return $this->render('PumWoodworkBundle:Beam:clone.html.twig', array(
            'beam' => $beam,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route(path="/beams/{beamName}/delete", name="ww_beam_delete")
     * @ParamConverter("beam", class="Beam")
     */
    public function deleteAction(Beam $beam)
    {
        $this->assertGranted('ROLE_WW_BEAMS');

        $manager = $this->get('pum');

        if (!$beam->isDeletable()) {
            throw $this->createNotFoundException('Beam is not deletable');
        }

        $manager->deleteBeam($beam);
        $this->addSuccess('Beam successfully deleted');

        return $this->redirect($this->generateUrl('ww_beam_list'));
    }

    /**
     * @Route(path="/beams/{beamName}/export", name="ww_beam_export")
     * @ParamConverter("beam", class="Beam")
     */
    public function exportAction(Beam $beam)
    {
        $this->assertGranted('ROLE_WW_BEAMS');

        $manager = $this->get('pum');

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $d = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $beam->getName().'.json');
        $response->headers->set('Content-Disposition', $d);
        $response->setContent(json_encode($beam->toArray()));

        return $response;
    }

    /**
     * @Route(path="/beams/import", name="ww_beam_import")
     */
    public function importAction(Request $request)
    {
        $this->assertGranted('ROLE_WW_BEAMS');

        $manager = $this->get('pum');

        $form = $this->createForm('ww_beam_import', new Beam());
        if ($request->isMethod('POST') && $form->bind($request)->isValid()) {
            if (!$arrayedBeam = json_decode(file_get_contents($form->get('file')->getData()->getPathName()), true)) {
                $form->addError(new FormError('File is invalid json'));
            } else {
                try {
                    $beam = Beam::createFromArray($arrayedBeam)->setName($form->get('name')->getData());

                    $manager->saveBeam($beam);

                    $this->addSuccess('Beam successfully imported');

                    return $this->redirect($this->generateUrl('ww_beam_list'));
                } catch (\InvalidArgumentException $e) {
                    $form->addError(new FormError(sprintf('Json content is invalid : %s', $e->getMessage())));
                }
            }
        }

        return $this->render('PumWoodworkBundle:Beam:import.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
