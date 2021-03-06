<?php

namespace Pum\Bundle\WoodworkBundle\Controller;

use Pum\Bundle\AppBundle\Entity\Permission;
use Pum\Bundle\AppBundle\Entity\PermissionRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class PermissionController extends Controller
{
    /**
     * @Route(path="/permissions", name="ww_permission_list")
     */
    public function listAction(Request $request)
    {
        $repository = $this->getPermissionRepository();

        return $this->render('PumWoodworkBundle:Permission:list.html.twig', array(
                'pager' => $repository->getPage($request->query->get('page', 1))
            ));
    }

    /**
     * @Route(path="/permissions/{id}/edit", name="ww_permission_edit")
     */
    public function editAction(Request $request, $id)
    {
        $repository = $this->getPermissionRepository();

        $this->throwNotFoundUnless($permission = $repository->find($id));
        $form = $this->createForm('pum_permission', $permission);
        $permissionView = clone $permission;

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $repository->save($permission);
            $this->addSuccess($this->get('translator')->trans('permission.updated', array(), 'pum'));

            return $this->redirect($this->generateUrl('ww_permission_list'));
        }

        return $this->render('PumWoodworkBundle:Permission:edit.html.twig', array(
                'form' => $form->createView(),
                'permission' => $permissionView,
                'pager' => $repository->getPage()
            ));
    }

    /**
     * @Route(path="/permissions/create", name="ww_permission_create")
     */
    public function createAction(Request $request)
    {
        $permission = new Permission();

        if ($groupId = $request->query->get('group')) {
            $groupRepository = $this->getGroupRepository();
            $this->throwNotFoundUnless($group = $groupRepository->find($groupId));
            $permission->setGroup($group);
        }

        if ($attribute = $request->query->get('attribute')) {
            $permission->setAttribute($attribute);
        }

        if ($projectName = $request->query->get('project')) {
            $objectFactory = $this->get('pum_core.object_factory');
            $this->throwNotFoundUnless($project = $objectFactory->getProject($projectName));
            $permission->setProject($project);

            if ($beamName = $request->query->get('beam')) {
                $this->throwNotFoundUnless($beam = $objectFactory->getBeam($beamName));
                $permission->setBeam($beam);

                if ($objectName = $request->query->get('object')) {
                    $this->throwNotFoundUnless($object = $objectFactory->getDefinition($projectName, $objectName));
                    $permission->setObject($object);
                }
            }
        }

        $repository = $this->getPermissionRepository();

        $form = $this->createForm('pum_permission', $permission);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $repository->save($permission = $form->getData());

            $this->addSuccess($this->get('translator')->trans('permission.created', array(), 'pum'));

            return $this->redirect($this->generateUrl('ww_permission_list'));
        }

        return $this->render('PumWoodworkBundle:Permission:create.html.twig', array(
                'form' => $form->createView(),
                'pager' => $repository->getPage()
            ));
    }

    /**
     * @Route(path="/permissions/{id}/delete", name="ww_permission_delete")
     */
    public function deleteAction($id)
    {
        $repository = $this->getPermissionRepository();

        $this->throwNotFoundUnless($permission = $repository->find($id));

        $repository->delete($permission);
        $this->addSuccess($this->get('translator')->trans('permission.deleted', array(), 'pum'));

        return $this->redirect($this->generateUrl('ww_permission_list'));
    }

    /**
     * Verifies permissions and return permission repository (or null if disabled).
     *
     * @return PermissionRepository
     */
    private function getPermissionRepository()
    {
        $this->assertGranted('ROLE_WW_USERS');

        if (!$this->container->has('pum.permission_repository')) {
            return null;
        }

        return $this->get('pum.permission_repository');
    }

    private function getGroupRepository()
    {
        $this->assertGranted('ROLE_WW_USERS');

        if (!$this->container->has('pum.group_repository')) {
            return null;
        }

        return $this->get('pum.group_repository');
    }
}
