<?php

namespace Pum\Bundle\ProjectAdminBundle\Controller;

use Pum\Core\Definition\Beam;
use Pum\Core\Definition\ObjectDefinition;
use Pum\Core\Definition\TableView;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class TableViewController extends Controller
{
    const DEFAULT_NAME = 'Default';

    /**
     * @Route(path="/{_project}/{beamName}/{name}/tableview/create", name="pa_tableview_create")
     * @ParamConverter("beam", class="Beam")
     * @ParamConverter("object", class="ObjectDefinition", options={"objectDefinitionName" = "name"})
     */
    public function createAction(Request $request, Beam $beam, ObjectDefinition $object)
    {
        $this->assertGranted('ROLE_PA_VIEW_EDIT');

        $form = $this->createForm('pa_tableview', $object->createTableView());

        if ($request->isMethod('POST') && $form->bind($request)->isValid()) {
            $this->get('pum')->saveBeam($beam);
            $this->addSuccess('TableView "' .$form->getData()->getName(). '" successfully created');

            return $this->redirect($this->generateUrl('pa_tableview_edit', array('beamName' => $beam->getName(), 'name' => $object->getName(), 'tableViewName' => $form->getData()->getName())));
        }

        return $this->render('PumProjectAdminBundle:TableView:create.html.twig', array(
            'beam' => $beam,
            'object_definition' => $object,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route(path="/{_project}/{beamName}/{name}/tableview/{tableViewName}/edit/{type}", name="pa_tableview_edit")
     * @ParamConverter("beam", class="Beam")
     * @ParamConverter("object", class="ObjectDefinition", options={"objectDefinitionName" = "name"})
     */
    public function editAction(Request $request, Beam $beam, ObjectDefinition $object, $tableViewName, $type = 'columns')
    {
        $this->assertGranted('ROLE_PA_VIEW_EDIT');

        $tableView = $object->getTableView($tableViewName);
        $form = $this->createForm('pa_tableview', $tableView, array('form_type' => $type));

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->get('pum')->saveBeam($beam);
            $this->addSuccess('TableView "'.$tableView->getName().'" successfully updated');

            return $this->redirect($this->generateUrl('pa_tableview_edit', array(
                'beamName'      => $beam->getName(),
                'name'          => $object->getName(),
                'tableViewName' => $tableView->getName(),
                'type'          => $type
            )));
        }

        return $this->render('PumProjectAdminBundle:TableView:edit.'.$type.'.html.twig', array(
            'beam'              => $beam,
            'object_definition' => $object,
            'table_view'        => $tableView,
            'form'              => $form->createView()
        ));
    }

    /**
     * @Route(path="/{_project}/{beamName}/{name}/tableview/{tableViewName}/delete", name="pa_tableview_delete")
     * @ParamConverter("beam", class="Beam")
     * @ParamConverter("object", class="ObjectDefinition", options={"objectDefinitionName" = "name"})
     */
    public function deleteAction(Beam $beam, ObjectDefinition $object, $tableViewName)
    {
        $this->assertGranted('ROLE_PA_VIEW_EDIT');
        
        $object->removeTableView($object->getTableView($tableViewName));
        $this->get('pum')->saveBeam($beam);
        $this->addSuccess('TableView "'.$tableViewName.'" successfully deleted');

        return $this->redirect($this->generateUrl('pa_object_list', array('beamName' => $beam->getName(), 'name' => $object->getName())));
    }
}
