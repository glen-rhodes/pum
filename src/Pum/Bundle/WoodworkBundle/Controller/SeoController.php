<?php

namespace Pum\Bundle\WoodworkBundle\Controller;

use Pum\Core\Seo\SeoSchema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class SeoController extends Controller
{
    /**
     * @Route(path="/routing/{formType}", name="ww_seo_schema_edit")
     */
    public function editAction(Request $request, $formType = "order")
    {
        $this->assertGranted('ROLE_WW_ROUTING');

        $seoSchema = new SeoSchema($this->get('pum'), $this->get('pum.context'));

        $form = $this->createForm('ww_seo_schema', $seoSchema, array('formType' => $formType));

        if ($request->getMethod() == 'POST' && $form->handleRequest($request)->isValid()) {
            $this->get('pum')->clearCache();
            $this->addSuccess('Routing schema successfully updated');

            return $this->redirect($this->generateUrl('ww_seo_schema_edit', array('formType' => $formType)));
        }

        return $this->render('PumWoodworkBundle:Seo:'.$formType.'.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
